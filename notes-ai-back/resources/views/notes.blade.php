<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Notes</title>
    <style>
        :root {
            --bg: #0f1115;
            --panel: #171a21;
            --border: #2a2e38;
            --text: #e8eaed;
            --muted: #9aa0ac;
            --accent: #6c8dff;
            --accent-hover: #5578e8;
            --danger: #e5626b;
            --radius: 10px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .wrap { max-width: 880px; margin: 0 auto; padding: 32px 20px 80px; }
        header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        h1 { font-size: 22px; margin: 0; }
        h1 span { color: var(--accent); }
        .subtitle { color: var(--muted); font-size: 13px; margin-top: 4px; }

        .search-row { display: flex; gap: 8px; margin-bottom: 20px; }
        input, textarea {
            width: 100%;
            background: var(--panel);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: var(--radius);
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
        }
        input:focus, textarea:focus { outline: none; border-color: var(--accent); }
        textarea { resize: vertical; min-height: 80px; }

        button {
            cursor: pointer;
            border: none;
            border-radius: var(--radius);
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            background: var(--accent);
            color: #fff;
        }
        button:hover { background: var(--accent-hover); }
        button.secondary { background: transparent; color: var(--text); border: 1px solid var(--border); font-weight: 500; }
        button.secondary:hover { background: var(--panel); }
        button.danger { background: transparent; color: var(--danger); border: 1px solid var(--border); font-weight: 500; }
        button.danger:hover { background: rgba(229, 98, 107, 0.12); }
        button:disabled { opacity: .5; cursor: not-allowed; }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px;
            margin-bottom: 14px;
        }
        .card-form { display: grid; gap: 10px; }
        .card-form .actions { display: flex; gap: 8px; justify-content: flex-end; }

        .note h3 { margin: 0 0 6px; font-size: 16px; }
        .note p.content { margin: 0 0 10px; color: #cfd3da; font-size: 14px; line-height: 1.5; white-space: pre-wrap; }
        .note .summary {
            background: rgba(108, 141, 255, 0.1);
            border: 1px solid rgba(108, 141, 255, 0.25);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 13px;
            color: #c7d1ff;
            margin-bottom: 10px;
        }
        .note .meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 12px; color: var(--muted); margin-bottom: 10px; }
        .badge {
            background: rgba(108, 141, 255, 0.15);
            color: var(--accent);
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 600;
        }
        .note .actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .empty { text-align: center; color: var(--muted); padding: 40px 0; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 14px; margin-top: 20px; color: var(--muted); font-size: 13px; }

        .toast {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            background: #23262f; border: 1px solid var(--border); color: var(--text);
            padding: 10px 18px; border-radius: var(--radius); font-size: 13px; display: none;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <div>
                <h1>AI <span>Notes</span></h1>
                <div class="subtitle">Notes CRUD + AI semantic search + AI summaries</div>
            </div>
            <button id="new-note-btn">+ New note</button>
        </header>

        <div class="search-row">
            <input id="search-input" type="text" placeholder="Semantic search (e.g. 'money savings', 'staying fit')...">
            <button id="search-btn" class="secondary">Search</button>
            <button id="clear-search-btn" class="secondary" style="display:none">Clear</button>
        </div>

        <div id="new-note-card" class="card card-form" style="display:none">
            <input id="new-title" type="text" placeholder="Title">
            <textarea id="new-content" placeholder="Write your note..."></textarea>
            <div class="actions">
                <button class="secondary" id="cancel-new-btn">Cancel</button>
                <button id="save-new-btn">Save note</button>
            </div>
        </div>

        <div id="notes-list"></div>
        <div id="pagination" class="pagination" style="display:none"></div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        // ---------------------------------------------------------------
        // Tiny vanilla-JS frontend for the Notes API. No build step, no
        // framework - everything talks to /api/notes via fetch().
        // ---------------------------------------------------------------
        const API = '/api/notes';
        let currentPage = 1;
        let searchMode = false;

        const el = (id) => document.getElementById(id);
        const notesList = el('notes-list');
        const pagination = el('pagination');

        function toast(message) {
            const t = el('toast');
            t.textContent = message;
            t.style.display = 'block';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => (t.style.display = 'none'), 2500);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.innerText = str ?? '';
            return div.innerHTML;
        }

        async function api(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(options.headers || {}),
                },
            });

            if (response.status === 204) return null;

            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = body.errors
                    ? Object.values(body.errors).flat().join(' ')
                    : (body.message || 'Something went wrong.');
                throw new Error(message);
            }

            return body;
        }

        function noteCardHtml(note) {
            const score = note.relevance_score !== null && note.relevance_score !== undefined
                ? `<span class="badge">match ${Math.round(note.relevance_score * 100)}%</span>`
                : '';

            const summaryHtml = note.summary
                ? `<div class="summary">✨ ${escapeHtml(note.summary)}</div>`
                : '';

            return `
                <div class="card note" data-id="${note.id}">
                    <h3>${escapeHtml(note.title)}</h3>
                    <div class="meta">
                        ${score}
                        <span>Updated ${new Date(note.updated_at).toLocaleString()}</span>
                    </div>
                    ${summaryHtml}
                    <p class="content">${escapeHtml(note.content)}</p>
                    <div class="actions">
                        <button class="secondary" data-action="summarize">${note.has_summary ? 'Regenerate summary' : 'Generate AI summary'}</button>
                        <button class="secondary" data-action="edit">Edit</button>
                        <button class="danger" data-action="delete">Delete</button>
                    </div>
                </div>
            `;
        }

        function render(notes) {
            notesList.innerHTML = notes.length
                ? notes.map(noteCardHtml).join('')
                : '<div class="empty">No notes yet. Create your first one above.</div>';
        }

        function renderPagination(meta) {
            if (!meta || searchMode) {
                pagination.style.display = 'none';
                return;
            }
            pagination.style.display = 'flex';
            pagination.innerHTML = `
                <button class="secondary" id="prev-page" ${meta.current_page <= 1 ? 'disabled' : ''}>&larr; Prev</button>
                <span>Page ${meta.current_page} of ${meta.last_page} (${meta.total} notes)</span>
                <button class="secondary" id="next-page" ${meta.current_page >= meta.last_page ? 'disabled' : ''}>Next &rarr;</button>
            `;
            el('prev-page').onclick = () => loadNotes(currentPage - 1);
            el('next-page').onclick = () => loadNotes(currentPage + 1);
        }

        async function loadNotes(page = 1) {
            currentPage = page;
            searchMode = false;
            el('clear-search-btn').style.display = 'none';
            try {
                const result = await api(`${API}?page=${page}&limit=10`);
                render(result.data);
                renderPagination(result.meta);
            } catch (e) {
                toast(e.message);
            }
        }

        async function runSearch() {
            const q = el('search-input').value.trim();
            if (!q) return loadNotes(1);

            searchMode = true;
            el('clear-search-btn').style.display = 'inline-block';
            try {
                const result = await api(`${API}/search?q=${encodeURIComponent(q)}`);
                render(result.data);
                renderPagination(null);
            } catch (e) {
                toast(e.message);
            }
        }

        // --- Create -------------------------------------------------------
        el('new-note-btn').onclick = () => (el('new-note-card').style.display = 'grid');
        el('cancel-new-btn').onclick = () => (el('new-note-card').style.display = 'none');

        el('save-new-btn').onclick = async () => {
            const title = el('new-title').value.trim();
            const content = el('new-content').value.trim();
            if (!title || !content) return toast('Title and content are both required.');

            try {
                await api(API, { method: 'POST', body: JSON.stringify({ title, content }) });
                el('new-title').value = '';
                el('new-content').value = '';
                el('new-note-card').style.display = 'none';
                toast('Note created.');
                loadNotes(1);
            } catch (e) {
                toast(e.message);
            }
        };

        // --- Search ---------------------------------------------------------
        el('search-btn').onclick = runSearch;
        el('search-input').addEventListener('keydown', (e) => e.key === 'Enter' && runSearch());
        el('clear-search-btn').onclick = () => {
            el('search-input').value = '';
            loadNotes(1);
        };

        // --- Per-note actions (edit / delete / summarize), delegated ------
        notesList.addEventListener('click', async (e) => {
            const button = e.target.closest('button[data-action]');
            if (!button) return;

            const card = button.closest('.note');
            const id = card.dataset.id;
            const action = button.dataset.action;

            if (action === 'delete') {
                if (!confirm('Delete this note?')) return;
                await api(`${API}/${id}`, { method: 'DELETE' });
                toast('Note deleted.');
                return searchMode ? runSearch() : loadNotes(currentPage);
            }

            if (action === 'summarize') {
                button.disabled = true;
                button.textContent = 'Summarizing...';
                try {
                    await api(`${API}/${id}/summary`, { method: 'POST' });
                    toast('Summary generated.');
                    searchMode ? runSearch() : loadNotes(currentPage);
                } catch (err) {
                    toast(err.message);
                    button.disabled = false;
                }
                return;
            }

            if (action === 'edit') {
                const titleEl = card.querySelector('h3');
                const contentEl = card.querySelector('p.content');
                const currentTitle = titleEl.textContent;
                const currentContent = contentEl.textContent;

                card.querySelector('.actions').outerHTML = `
                    <div class="card-form" style="margin-top:10px">
                        <input class="edit-title" value="${escapeHtml(currentTitle)}">
                        <textarea class="edit-content">${escapeHtml(currentContent)}</textarea>
                        <div class="actions">
                            <button class="secondary" data-action="cancel-edit">Cancel</button>
                            <button data-action="save-edit">Save</button>
                        </div>
                    </div>
                `;
                return;
            }

            if (action === 'cancel-edit') {
                return searchMode ? runSearch() : loadNotes(currentPage);
            }

            if (action === 'save-edit') {
                const title = card.querySelector('.edit-title').value.trim();
                const content = card.querySelector('.edit-content').value.trim();
                if (!title || !content) return toast('Title and content are both required.');

                try {
                    await api(`${API}/${id}`, { method: 'PUT', body: JSON.stringify({ title, content }) });
                    toast('Note updated.');
                    searchMode ? runSearch() : loadNotes(currentPage);
                } catch (err) {
                    toast(err.message);
                }
            }
        });

        loadNotes(1);
    </script>
</body>
</html>
