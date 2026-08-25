# AI Notes

An AI-powered Notes Management System - Laravel API + a Chakra UI/React
frontend.

```
notes-ai-app/
├── notes-ai-back/     Laravel 11 API - CRUD, semantic search, AI summaries
│                       -> see notes-ai-back/README.md for full setup,
│                          API docs, database schema, and AI integration
│                          details.
└── notes-ai-front/     React + Chakra UI frontend
                        -> see notes-ai-front/README.md for setup.
```

## Quick start

**Backend** (Laravel API on `http://localhost:8000`):

```bash
cd notes-ai-back
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
```

**Frontend** (React app on `http://localhost:5173`):

```bash
cd notes-ai-front
npm install
npm run dev
```

The frontend currently runs on mock, in-memory data so it can be designed
and reviewed independently. Wiring it up to the real API above is the next
step - see `notes-ai-front/src/api/notesApi.js` for exactly where that
swap happens.

Full documentation (setup, API reference, DB schema, AI tools/prompts
used, architecture, security) lives in each project's own README:

- [`notes-ai-back/README.md`](notes-ai-back/README.md)
- [`notes-ai-front/README.md`](notes-ai-front/README.md)
