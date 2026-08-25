import {
  Box,
  Button,
  Container,
  HStack,
  SimpleGrid,
  Stack,
  Text,
} from '@chakra-ui/react'
import { useState } from 'react'
import { LuNotebookPen } from 'react-icons/lu'
import { DeleteConfirmDialog } from '../components/DeleteConfirmDialog'
import { Header } from '../components/Header'
import { NoteCard } from '../components/NoteCard'
import { NoteFormDialog } from '../components/NoteFormDialog'
import { SearchBar } from '../components/SearchBar'
import { EmptyState } from '../components/ui/empty-state'
import {
  PaginationItems,
  PaginationNextTrigger,
  PaginationPrevTrigger,
  PaginationRoot,
} from '../components/ui/pagination'
import { Skeleton } from '../components/ui/skeleton'
import { useNotes } from '../hooks/useNotes'

export default function NotesPage() {
  const {
    notes,
    isLoading,
    pageMeta,
    page,
    isSearching,
    searchTerm,
    setSearchTerm,
    goToPage,
    search,
    clearSearch,
    addNote,
    editNote,
    removeNote,
    generateSummary,
  } = useNotes()

  const [formNote, setFormNote] = useState(null) // null = create, object = edit
  const [isFormOpen, setIsFormOpen] = useState(false)
  const [noteToDelete, setNoteToDelete] = useState(null)
  const [summarizingId, setSummarizingId] = useState(null)

  const openCreateForm = () => {
    setFormNote(null)
    setIsFormOpen(true)
  }

  const openEditForm = (note) => {
    setFormNote(note)
    setIsFormOpen(true)
  }

  const handleFormSubmit = async (values) => {
    if (formNote) {
      await editNote(formNote.id, values)
    } else {
      await addNote(values)
    }
  }

  const handleSummarize = async (id) => {
    setSummarizingId(id)
    await generateSummary(id)
    setSummarizingId(null)
  }

  const handleDeleteConfirm = async (note) => {
    setNoteToDelete(null)
    await removeNote(note.id)
  }

  return (
    <Box minH="100vh" bg="bg">
      <Container maxW="5xl" py="10">
        <Stack gap="6">
          <Header />

          <HStack justify="space-between" wrap="wrap" gap="3">
            <Box flex="1" minW="280px">
              <SearchBar
                value={searchTerm}
                onChange={setSearchTerm}
                onSearch={search}
                onClear={clearSearch}
                isSearching={isSearching}
              />
            </Box>
            <Button colorPalette="purple" onClick={openCreateForm}>
              <LuNotebookPen /> New note
            </Button>
          </HStack>

          {isSearching && !isLoading && (
            <Text fontSize="sm" color="fg.muted">
              {notes.length} result{notes.length === 1 ? '' : 's'} for "{searchTerm}"
            </Text>
          )}

          {isLoading ? (
            <SimpleGrid columns={{ base: 1, md: 2 }} gap="4">
              {Array.from({ length: 4 }).map((_, i) => (
                <Skeleton key={i} height="160px" borderRadius="lg" />
              ))}
            </SimpleGrid>
          ) : notes.length === 0 ? (
            <EmptyState
              title={isSearching ? 'No matching notes' : 'No notes yet'}
              description={
                isSearching
                  ? 'Try a different search term.'
                  : 'Create your first note to get started.'
              }
            />
          ) : (
            <SimpleGrid columns={{ base: 1, md: 2 }} gap="4">
              {notes.map((note) => (
                <NoteCard
                  key={note.id}
                  note={note}
                  onEdit={openEditForm}
                  onDelete={setNoteToDelete}
                  onSummarize={handleSummarize}
                  isSummarizing={summarizingId === note.id}
                />
              ))}
            </SimpleGrid>
          )}

          {pageMeta && pageMeta.last_page > 1 && !isSearching && (
            <PaginationRoot
              count={pageMeta.total}
              pageSize={pageMeta.per_page}
              page={page}
              onPageChange={(e) => goToPage(e.page)}
              alignSelf="center"
            >
              <HStack>
                <PaginationPrevTrigger />
                <PaginationItems />
                <PaginationNextTrigger />
              </HStack>
            </PaginationRoot>
          )}
        </Stack>
      </Container>

      <NoteFormDialog
        isOpen={isFormOpen}
        onOpenChange={setIsFormOpen}
        note={formNote}
        onSubmit={handleFormSubmit}
      />

      <DeleteConfirmDialog
        note={noteToDelete}
        onOpenChange={(open) => !open && setNoteToDelete(null)}
        onConfirm={handleDeleteConfirm}
      />
    </Box>
  )
}
