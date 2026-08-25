// Notes API - one function per notes-ai-back endpoint. Configure the
// backend URL via VITE_API_BASE_URL in .env (see .env.example).
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'

const JSON_HEADERS = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
}

/** Turns a failed response into an Error with `.status` and `.errors`. */
async function toApiError(response) {
  const body = await response.json().catch(() => ({}))
  const error = new Error(body.message || 'Something went wrong.')
  error.status = response.status
  error.errors = body.errors
  return error
}

/** GET /api/notes?page=&limit= */
export async function listNotes({ page = 1, limit = 10 } = {}) {
  const response = await fetch(`${API_BASE_URL}/notes?page=${page}&limit=${limit}`, {
    headers: JSON_HEADERS,
  })
  if (!response.ok) throw await toApiError(response)

  const { data, meta } = await response.json()
  return { data, meta }
}

/** GET /api/notes/{id} */
export async function getNote(id) {
  const response = await fetch(`${API_BASE_URL}/notes/${id}`, { headers: JSON_HEADERS })
  if (!response.ok) throw await toApiError(response)
  return response.json()
}

/** POST /api/notes */
export async function createNote({ title, content }) {
  const response = await fetch(`${API_BASE_URL}/notes`, {
    method: 'POST',
    headers: JSON_HEADERS,
    body: JSON.stringify({ title, content }),
  })
  if (!response.ok) throw await toApiError(response)
  return response.json()
}

/** PUT /api/notes/{id} */
export async function updateNote(id, { title, content }) {
  const response = await fetch(`${API_BASE_URL}/notes/${id}`, {
    method: 'PUT',
    headers: JSON_HEADERS,
    body: JSON.stringify({ title, content }),
  })
  if (!response.ok) throw await toApiError(response)
  return response.json()
}

/** DELETE /api/notes/{id} */
export async function deleteNote(id) {
  const response = await fetch(`${API_BASE_URL}/notes/${id}`, {
    method: 'DELETE',
    headers: JSON_HEADERS,
  })
  if (!response.ok) throw await toApiError(response)
  return null
}

/** POST /api/notes/{id}/summary */
export async function summarizeNote(id) {
  const response = await fetch(`${API_BASE_URL}/notes/${id}/summary`, {
    method: 'POST',
    headers: JSON_HEADERS,
  })
  if (!response.ok) throw await toApiError(response)
  return response.json()
}

/** GET /api/notes/search?q=&limit= */
export async function searchNotes({ q, limit = 10 }) {
  const params = new URLSearchParams({ q, limit })
  const response = await fetch(`${API_BASE_URL}/notes/search?${params}`, {
    headers: JSON_HEADERS,
  })
  if (!response.ok) throw await toApiError(response)
  return response.json()
}
