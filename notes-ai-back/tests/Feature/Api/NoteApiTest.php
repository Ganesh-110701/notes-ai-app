<?php

namespace Tests\Feature\Api;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Runs against the local/offline AI fallback (no OPENAI_API_KEY in testing).
class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_note(): void
    {
        $response = $this->postJson('/api/notes', [
            'title' => 'My first note',
            'content' => 'Some interesting content about Laravel.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My first note')
            ->assertJsonPath('data.content', 'Some interesting content about Laravel.')
            ->assertJsonPath('data.has_summary', false);

        $this->assertDatabaseHas('notes', ['title' => 'My first note']);

        $note = Note::first();
        $this->assertIsArray($note->embedding);
        $this->assertNotEmpty($note->embedding);
    }

    public function test_it_rejects_invalid_input(): void
    {
        $response = $this->postJson('/api/notes', [
            'title' => '',
            'content' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_it_lists_notes_with_pagination(): void
    {
        Note::factory()->count(15)->create();

        $response = $this->getJson('/api/notes?page=1&limit=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertSame(15, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    public function test_it_shows_a_single_note(): void
    {
        $note = Note::factory()->create();

        $this->getJson("/api/notes/{$note->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $note->id);
    }

    public function test_it_returns_404_for_a_missing_note(): void
    {
        $this->getJson('/api/notes/999999')->assertNotFound();
    }

    public function test_it_updates_a_note_and_recomputes_its_embedding(): void
    {
        $note = Note::factory()->create(['title' => 'Old title']);
        $originalEmbedding = $note->embedding;

        $response = $this->patchJson("/api/notes/{$note->id}", [
            'title' => 'New title',
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'New title');

        $note->refresh();
        $this->assertSame('New title', $note->title);
        $this->assertNotSame($originalEmbedding, $note->embedding);
    }

    public function test_it_deletes_a_note(): void
    {
        $note = Note::factory()->create();

        $this->deleteJson("/api/notes/{$note->id}")->assertNoContent();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_it_generates_and_caches_an_ai_summary(): void
    {
        $note = Note::factory()->create([
            'content' => 'The quick brown fox jumps over the lazy dog. '
                .'Dogs and foxes are both members of the Canidae family. '
                .'This sentence is unrelated to the previous two entirely. '
                .'A final sentence wraps up the note nicely.',
        ]);

        $response = $this->postJson("/api/notes/{$note->id}/summary");

        $response->assertOk()->assertJsonStructure(['id', 'summary']);
        $this->assertNotEmpty($response->json('summary'));

        $note->refresh();
        $this->assertSame($response->json('summary'), $note->summary);
    }

    public function test_semantic_search_ranks_relevant_notes_first(): void
    {
        $budget = Note::factory()->create([
            'title' => 'Monthly budget',
            'content' => 'Track savings, rent, and monthly expenses carefully.',
        ]);
        Note::factory()->create([
            'title' => 'Workout plan',
            'content' => 'Leg day, push day, pull day, and plenty of stretching.',
        ]);

        foreach (Note::all() as $note) {
            $note->embedding = app(\App\Services\AI\EmbeddingService::class)
                ->embed($note->title.' '.$note->content);
            $note->save();
        }

        $response = $this->getJson('/api/notes/search?q=savings and rent');

        $response->assertOk();
        $this->assertSame($budget->id, $response->json('data.0.id'));
    }
}
