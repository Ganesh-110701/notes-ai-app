<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Services\AI\EmbeddingService;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        $embeddings = app(EmbeddingService::class);

        $notes = [
            [
                'title' => 'Grocery list',
                'content' => 'Buy milk, eggs, bread, spinach, and coffee beans. Remember to check '
                    .'if we still have olive oil before leaving the store.',
            ],
            [
                'title' => 'Monthly budget planning',
                'content' => 'Review rent, utilities, and subscription costs. Set aside savings for '
                    .'an emergency fund and track spending against last month.',
            ],
            [
                'title' => 'Laravel project ideas',
                'content' => 'Build a notes app with an API backend, add authentication, and '
                    .'experiment with queues for background jobs like sending emails.',
            ],
            [
                'title' => 'Workout plan',
                'content' => 'Monday: legs and core. Wednesday: upper body push. Friday: upper body '
                    .'pull. Stretch every morning and stay hydrated throughout the day.',
            ],
            [
                'title' => 'Book notes: Atomic Habits',
                'content' => 'Small, consistent changes compound over time. Focus on systems rather '
                    .'than goals, and make good habits obvious, attractive, easy, and satisfying.',
            ],
            [
                'title' => 'Trip to Goa',
                'content' => 'Flights booked for next month. Need to pack sunscreen, swimwear, and '
                    .'a good book. Look into beach resorts and local seafood restaurants.',
            ],
        ];

        foreach ($notes as $data) {
            $note = new Note([
                'title' => $data['title'],
                'content' => $data['content'],
            ]);
            $note->embedding = $embeddings->embed($data['title'].' '.$data['content']);
            $note->save();
        }
    }
}
