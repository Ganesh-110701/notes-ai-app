import { useCallback, useEffect, useState } from 'react'
import { toaster } from '../components/ui/toaster'
import {
  createNote,
  deleteNote,
  listNotes,
  searchNotes,
  summarizeNote,
  updateNote,
} from '../api/notesApi'

const PAGE_SIZE = 6

export function useNotes() {
  const [notes, setNotes] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [pageMeta, setPageMeta] = useState(null)
  const [page, setPage] = useState(1)
  const [searchTerm, setSearchTerm] = useState('')
  const [isSearching, setIsSearching] = useState(false)

  const loadPage = useCallback(async (pageToLoad) => {
    setIsLoading(true)
    try {
      const result = await listNotes({ page: pageToLoad, limit: PAGE_SIZE })
      setNotes(result.data)
      setPageMeta(result.meta)
      setPage(pageToLoad)
    } catch {
      toaster.create({ type: 'error', title: 'Could not load notes.' })
    } finally {
      setIsLoading(false)
    }
  }, [])

  const runSearch = useCallback(async (q) => {
    if (!q.trim()) return
    setIsLoading(true)
    setIsSearching(true)
    try {
      const result = await searchNotes({ q })
      setNotes(result.data)
      setPageMeta(null)
    } catch {
      toaster.create({ type: 'error', title: 'Search failed.' })
    } finally {
      setIsLoading(false)
    }
  }, [])

  const clearSearch = useCallback(() => {
    setSearchTerm('')
    setIsSearching(false)
    loadPage(1)
  }, [loadPage])

  useEffect(() => {
    loadPage(1)
  }, [loadPage])

  const refresh = useCallback(() => {
    return isSearching ? runSearch(searchTerm) : loadPage(page)
  }, [isSearching, runSearch, searchTerm, loadPage, page])

  const addNote = useCallback(
    async ({ title, content }) => {
      try {
        await createNote({ title, content })
        toaster.create({ type: 'success', title: 'Note created.' })
        await loadPage(1)
        return true
      } catch (error) {
        if (error.status === 422) throw error
        toaster.create({ type: 'error', title: 'Could not create note.' })
        return false
      }
    },
    [loadPage],
  )

  const editNote = useCallback(
    async (id, { title, content }) => {
      try {
        await updateNote(id, { title, content })
        toaster.create({ type: 'success', title: 'Note updated.' })
        await refresh()
        return true
      } catch (error) {
        if (error.status === 422) throw error
        toaster.create({ type: 'error', title: 'Could not update note.' })
        return false
      }
    },
    [refresh],
  )

  const removeNote = useCallback(
    async (id) => {
      try {
        await deleteNote(id)
        toaster.create({ type: 'success', title: 'Note deleted.' })
        await refresh()
      } catch {
        toaster.create({ type: 'error', title: 'Could not delete note.' })
      }
    },
    [refresh],
  )

  const generateSummary = useCallback(
    async (id) => {
      try {
        await summarizeNote(id)
        toaster.create({ type: 'success', title: 'Summary generated.' })
        await refresh()
      } catch {
        toaster.create({ type: 'error', title: 'Could not generate summary.' })
      }
    },
    [refresh],
  )

  return {
    notes,
    isLoading,
    pageMeta,
    page,
    isSearching,
    searchTerm,
    setSearchTerm,
    goToPage: loadPage,
    search: runSearch,
    clearSearch,
    addNote,
    editNote,
    removeNote,
    generateSummary,
  }
}
