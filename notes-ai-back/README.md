# AI Notes API

A small AI-powered Notes Management System built with **Laravel 11**:
Notes CRUD, AI-powered semantic search, AI-generated summaries, pagination,
a simple frontend, and basic API security (validation, rate limiting).

> **Repo layout:** this `notes-ai-back` folder is the Laravel API (this
> README). Its sibling [`../notes-ai-front`](../notes-ai-front) is a newer
> Chakra UI/React frontend being built to replace the simple Blade view
> below - see that folder's own README for its setup.

---

## 1. Tech stack

| Layer     | Choice                                                          |
|-----------|------------------------------------------------------------------|
| Backend   | Laravel 11 (PHP 8.2+)                                            |
| Database  | MySQL (works on PostgreSQL too - it's just Eloquent + migrations)|
| Frontend  | Plain HTML + CSS + vanilla JS (one Blade view, no build step) - see the repo-layout note above for the newer React UI |
| AI        | OpenAI API (embeddings + chat) **with a built-in offline fallback**|

---

## 2. Setup instructions

```bash
# 0. From the repo root, move into the backend folder
cd notes-ai-back

# 1. Install PHP dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Edit .env - set your database credentials
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=notes_ai
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Create the database (MySQL example)
mysql -u root -e "CREATE DATABASE notes_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Run migrations + seed some demo notes
php artisan migrate --seed

# 6. Start the app
php artisan serve
```

Open **http://127.0.0.1:8000** for the frontend. The API lives under
**http://127.0.0.1:8000/api**.

### Enabling real AI (optional)

By default `OPENAI_API_KEY` in `.env` is empty, which means the app runs
entirely on its **local, offline AI fallback** (see [§5](#5-ai-integration)
below) - no external calls, nothing to sign up for, tests stay deterministic.

To use real OpenAI models instead, just set in `.env`:

```
OPENAI_API_KEY=sk-...
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_CHAT_MODEL=gpt-4o-mini
```

No code changes needed - the app detects the key and switches automatically
(see `App\Providers\AppServiceProvider::register()`).

### Running tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database (configured in
`phpunit.xml`) and the local AI fallback, so they're fast and need no
external services.

### Docker (bonus)

```bash
docker compose up --build
```

This starts the app (port 8000) and a MySQL 8 container, runs migrations +
seeding automatically, and is ready to use immediately.

---

## 3. Database schema

One table, `notes`:

| Column       | Type      | Notes                                              |
|--------------|-----------|-----------------------------------------------------|
| id           | bigint PK |                                                       |
| title        | string    | required                                             |
| content      | text      | required                                             |
| summary      | text null | cached AI-generated summary; cleared when content changes |
| embedding    | json null | vector used for semantic search (see below)          |
| created_at   | timestamp |                                                       |
| updated_at   | timestamp |                                                       |

`embedding` is stored as JSON rather than a native vector column so the
app runs on plain MySQL/PostgreSQL with no extra extensions (no `pgvector`
required). Similarity is computed in PHP - simple and easy to follow, and
plenty fast at notes-app scale. See [§5](#5-ai-integration) for the
production-scale alternative.

---

## 4. API documentation

All endpoints are prefixed with `/api`, return JSON, and are rate-limited.
A machine-readable spec is also in [`docs/openapi.yaml`](docs/openapi.yaml)
(open it at [editor.swagger.io](https://editor.swagger.io) to browse it).

| Method | Endpoint                    | Description                                   |
|--------|------------------------------|-----------------------------------------------|
| GET    | `/api/notes?page=1&limit=10` | Paginated list of notes, newest first         |
| POST   | `/api/notes`                  | Create a note                                 |
| GET    | `/api/notes/{id}`             | Get a single note                             |
| PUT/PATCH | `/api/notes/{id}`           | Update a note (partial update supported)      |
| DELETE | `/api/notes/{id}`             | Delete a note                                 |
| POST   | `/api/notes/{id}/summary`     | Generate (and cache) an AI summary            |
| GET    | `/api/notes/search?q=...`     | AI-powered semantic search                    |

### Create a note

```
POST /api/notes
Content-Type: application/json

{ "title": "Grocery list", "content": "Milk, eggs, bread." }
```

`201 Created` →
```json
{ "data": { "id": 1, "title": "Grocery list", "content": "Milk, eggs, bread.", "summary": null, "has_summary": false, "created_at": "...", "updated_at": "..." } }
```

Validation failure → `422 Unprocessable Entity`
```json
{ "message": "The given data was invalid.", "errors": { "title": ["The title field is required."] } }
```

### Semantic search

```
GET /api/notes/search?q=money savings
```

Ranks notes by **meaning**, not exact keywords - a query like `money
savings` can surface a note titled *"Monthly budget planning"* even though
it shares no exact words with the query. Each result includes a
`relevance_score` (0-1 cosine similarity).

### AI summary

```
POST /api/notes/{id}/summary
```

Generates a short summary of the note and **caches** it on the note (so
calling it again is instant and doesn't re-spend an AI call), until the
note's title/content is edited, which clears the cached summary.

### Pagination

`GET /api/notes?page=1&limit=10` - `limit` is capped at 100 per page.
Standard Laravel paginator response shape: `data`, `links`, `meta`
(`current_page`, `last_page`, `total`, ...).

---

## 5. AI integration

Two things needed "AI": **semantic search** and **note summaries**. Both
are implemented behind small interfaces so the app can run two ways:

```
App\Services\AI\EmbeddingService   (interface)
 ├─ OpenAIEmbeddingService          - real embeddings via OpenAI's API
 └─ LocalEmbeddingService           - offline hashing-trick fallback

App\Services\AI\SummaryService     (interface)
 ├─ OpenAISummaryService            - real AI summaries via OpenAI's chat API
 └─ LocalSummaryService             - offline extractive-summary fallback
```

`App\Providers\AppServiceProvider::register()` picks the implementation at
runtime based on whether `OPENAI_API_KEY` is set - **no code needs to
change** to switch between them, and controllers/tests only ever depend on
the interfaces.

### How semantic search works

1. Every note's `title + content` is turned into a numeric vector (an
   "embedding") when the note is created or edited, and stored in the
   `embedding` column.
2. A search query is embedded the same way.
3. Every note's vector is compared to the query's vector using **cosine
   similarity** (`App\Services\AI\ConcreteEmbeddingService::cosineSimilarity`)
   - a score from -1 to 1 measuring how close in *meaning* two pieces of
   text are.
4. Notes are ranked by that score and returned.

With `OPENAI_API_KEY` set, step 1/2 call OpenAI's `text-embedding-3-small`
model (1536-dimensional vectors) - genuine semantic understanding (e.g. it
knows "puppy" and "dog" are related). Without a key, `LocalEmbeddingService`
uses the classic **hashing-trick bag-of-words** technique instead: words are
hashed into a fixed 256-slot vector, so notes sharing more (non-common)
words score higher. It's a lexical approximation, not true semantics, but
it needs no external API, keeps the app fully working offline, and keeps
the tests fast and deterministic.

> **At larger scale:** for a big note collection, comparing every note's
> vector in PHP on every search doesn't scale forever. A production version
> would store vectors in a dedicated vector index (e.g. PostgreSQL +
> `pgvector`, or a vector DB like Pinecone/Qdrant) so similarity search is
> done by the database itself. That's a swap-in for `EmbeddingService`,
> not a rewrite of the API surface.

### How summaries work

With `OPENAI_API_KEY` set, `OpenAISummaryService` asks a chat model
(`gpt-4o-mini` by default) to write a 2-3 sentence summary of the note.
Without a key, `LocalSummaryService` does simple **extractive
summarization**: it scores every sentence by how many notable words it
contains and keeps the best few (in their original order) - a well-known,
dependency-free summarization technique.

### AI tools used to build this project

- **Claude (Anthropic)** - used throughout via Claude Code to scaffold the
  Laravel project structure, write the CRUD/search/summary logic, the AI
  service abstractions above, the frontend, and this README.
- Prompted with the assignment brief itself (this repo's
  `PHP_AI_Notes_App_Assignment.pdf`), then iteratively: "add the notes CRUD
  API with validation and pagination", "implement semantic search using
  embeddings with an offline fallback so it doesn't require an API key",
  "add the AI summary endpoint with caching", "build a simple vanilla-JS
  frontend for this API", "add rate limiting and clean JSON error
  responses", "write feature tests for all of this".

### How the generated code was validated

- Every endpoint was manually exercised with `curl` (create, list with
  pagination, show, update/partial update, delete, 404 handling, 422
  validation errors, summary generation, semantic search ranking, and rate
  limiting hitting a `429`).
- `tests/Feature/Api/NoteApiTest.php` covers the same behaviour
  automatically (9 tests, run with `php artisan test`), including asserting
  that semantic search actually ranks a topically-relevant note above an
  unrelated one for a query with no shared keywords - the core claim of
  "semantic" search.
- Read through line-by-line for anything that looked like it *might* work
  but was subtly wrong (e.g. mass-assignment guarding on `embedding`, making
  sure `create()`/factories don't silently drop it - fixed by assigning it
  as a direct property, the same way the controller does).

---

## 6. Architecture

```
routes/api.php              → thin routing layer, one line per endpoint
  └─ Http/Controllers/Api/NoteController
        ├─ validates via Http/Requests/{Store,Update}NoteRequest
        ├─ talks to the Note model (Eloquent - parameterized queries only,
        │  so no SQL injection surface)
        ├─ delegates all AI work to Services/AI/* (interfaces)
        └─ shapes responses via Http/Resources/NoteResource
```

Design choices:

- **Thin controllers, small services.** The controller only orchestrates;
  all "how do we embed text" / "how do we summarize" logic lives in
  `App\Services\AI`, swappable and independently testable.
- **Form Requests for validation**, not inline `$request->validate()`, so
  rules are reusable and unit-testable, and failures always return a
  consistent `422` JSON shape (see `failedValidation()` overrides).
- **API Resources for output**, so the JSON shape is defined in one place
  and internal columns (like the raw `embedding` vector) never leak into
  responses.
- **Eloquent only, no raw SQL** anywhere in the app - all queries are
  parameter-bound by the query builder, which is the standard, effective
  defense against SQL injection in Laravel.

---

## 7. Security

- **SQL injection**: Eloquent/query builder everywhere (parameter binding);
  no raw/string-concatenated queries.
- **Validation**: dedicated Form Requests (`StoreNoteRequest`,
  `UpdateNoteRequest`) reject bad input with a clean `422` before it ever
  reaches the database.
- **Rate limiting**: `App\Providers\AppServiceProvider::boot()` defines two
  limiters - `api` (60 requests/min/IP, applied to the whole API) and a
  tighter `ai` (20 requests/min/IP) on the two AI-backed endpoints
  (`/notes/search`, `/notes/{id}/summary`), since those are the more
  expensive ones to abuse.
- **Secure API handling**: the OpenAI key stays server-side only (never
  sent to the frontend), API error responses are always clean JSON (see
  `bootstrap/app.php`'s `NotFoundHttpException` renderer) instead of a stack
  trace or HTML error page, and mass assignment is restricted via
  `Note::$fillable` (a client can never set `embedding`/`summary` directly).

For a real production deployment, also set `APP_DEBUG=false` and
`APP_ENV=production` in `.env`.

---

## 8. Project structure quick-reference

```
app/Http/Controllers/Api/NoteController.php   API endpoints
app/Http/Requests/{Store,Update}NoteRequest   Validation
app/Http/Resources/NoteResource.php           JSON shape
app/Models/Note.php                           Eloquent model
app/Services/AI/                              Embeddings + summaries (real + offline)
app/Providers/AppServiceProvider.php          AI bindings + rate limiters
database/migrations/..._create_notes_table    Schema
database/seeders/NoteSeeder.php               Demo notes
routes/api.php                                API routes
routes/web.php + resources/views/notes.blade.php   Frontend
tests/Feature/Api/NoteApiTest.php             Feature tests
docs/openapi.yaml                             OpenAPI/Swagger spec
docker-compose.yml, Dockerfile                Docker setup
```
