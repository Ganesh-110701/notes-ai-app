# AI Notes - Frontend

React + [Chakra UI v3](https://chakra-ui.com) frontend for the AI Notes app
(the Laravel API lives in the sibling [`../notes-ai-back`](../notes-ai-back)
folder).

## Status: wired to the real backend

`src/api/notesApi.js` calls the real `notes-ai-back` Laravel API via
`fetch()` - one function per endpoint, each returning exactly what that
endpoint returns (including the `422` validation-error and `404` shapes),
so components never deal with `fetch` directly.

(This started as a UI-only phase against in-memory mock data, built and
reviewed before the backend was wired up - kept deliberately as a thin,
swappable layer for exactly that reason.)

## Setup

```bash
npm install
cp .env.example .env   # set VITE_API_BASE_URL if the backend isn't on :8000
npm run dev
```

Opens at `http://localhost:5173`. **Requires `notes-ai-back` to be running**
(`cd ../notes-ai-back && php artisan serve`, default `http://localhost:8000`)
- the backend's default CORS config already allows any origin for `/api/*`,
so no extra CORS setup is needed for local dev.

```bash
npm run build    # production build (dist/)
npm run preview  # preview the production build locally
```

## Tech stack

| Layer        | Choice                                          |
|--------------|---------------------------------------------------|
| Build tool   | Vite                                               |
| UI library   | React 19                                           |
| Components   | Chakra UI v3 (`@chakra-ui/react`)                  |
| Icons        | react-icons                                        |
| Dark mode    | `next-themes` (via Chakra's `color-mode` snippet)  |

Chakra's official CLI (`@chakra-ui/cli snippet add ...`) was used to
generate the small `src/components/ui/*` wrapper components (dialog,
toaster, pagination, field, empty-state, skeleton, color-mode,
input-group) - these are Chakra's own recommended, copy-in building blocks
for v3, not hand-rolled.

## Project structure

```
src/
├── api/
│   └── notesApi.js        Real API layer - one fetch() call per backend endpoint
├── hooks/
│   └── useNotes.js        All notes state + CRUD/search/summary actions, wired to toasts
├── components/
│   ├── ui/                 Chakra v3 snippets (dialog, toaster, pagination, ...)
│   ├── Header.jsx
│   ├── SearchBar.jsx
│   ├── NoteCard.jsx
│   ├── NoteFormDialog.jsx  Create/edit note (one dialog, two modes)
│   └── DeleteConfirmDialog.jsx
├── pages/
│   └── NotesPage.jsx       Composes everything above into the actual page
├── App.jsx
└── main.jsx                 Wraps the app in Chakra's <Provider> + <Toaster>
```

## Features

- Paginated notes grid with loading skeletons and an empty state
- Create / edit (one shared dialog + form) / delete (with confirmation)
- "Generate AI summary" per note, shown in a highlighted summary box
- Semantic search bar with a relevance-match badge per result - ranked by
  the backend's AI embedding similarity, not exact keyword matches (see
  `notes-ai-back`'s README for how that actually works)
- Light/dark mode toggle
- Toast notifications for every action (success and failure)

## AI tools used

Built with **Claude (Anthropic)** via Claude Code - scaffolded with Vite,
had Chakra UI v3 added and its CLI snippets generated, then the
components/hook/API layer above were written iteratively from prompts such
as "build a Chakra UI frontend for this notes API, make it look good, use
mock data for now since we'll wire up the real backend next" followed by
"now wire it up to the real backend".

Validated by: `npm run build` (production build succeeds, no type/runtime
errors), a manual pass confirming every Chakra component/prop used
(`Card.*`, `Dialog.*`, semantic color tokens like `purple.subtle`/`fg.muted`)
exists in the installed `@chakra-ui/react` version, and end-to-end checks
against the real, running `notes-ai-back` API (CORS preflight, create,
list/paginate, update, delete, summary, and semantic search all confirmed
working together).
