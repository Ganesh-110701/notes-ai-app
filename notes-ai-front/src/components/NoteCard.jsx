import {
  Badge,
  Box,
  Card,
  HStack,
  IconButton,
  Text,
} from '@chakra-ui/react'
import { LuPencil, LuSparkles, LuTrash2 } from 'react-icons/lu'
import { Tooltip } from './ui/tooltip'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

export function NoteCard({ note, onEdit, onDelete, onSummarize, isSummarizing }) {
  return (
    <Card.Root
      variant="outline"
      transition="all 0.15s ease"
      _hover={{ transform: 'translateY(-2px)', shadow: 'md', borderColor: 'purple.300' }}
    >
      <Card.Body gap="3">
        <HStack justify="space-between" align="start">
          <Card.Title fontSize="md">{note.title}</Card.Title>
          {typeof note.relevance_score === 'number' && (
            <Badge colorPalette="purple" variant="surface" flexShrink="0">
              {Math.round(note.relevance_score * 100)}% match
            </Badge>
          )}
        </HStack>

        <Text fontSize="xs" color="fg.muted">
          Updated {formatDate(note.updated_at)}
        </Text>

        {note.has_summary && (
          <Box
            bg="purple.subtle"
            borderRadius="md"
            px="3"
            py="2"
            fontSize="sm"
            color="purple.fg"
          >
            <HStack gap="1.5" mb="0.5">
              <LuSparkles size={14} />
              <Text fontWeight="semibold" fontSize="xs">
                AI summary
              </Text>
            </HStack>
            {note.summary}
          </Box>
        )}

        <Text fontSize="sm" color="fg.muted" lineClamp="3">
          {note.content}
        </Text>
      </Card.Body>

      <Card.Footer justifyContent="flex-end" gap="1">
        <Tooltip content={note.has_summary ? 'Regenerate AI summary' : 'Generate AI summary'}>
          <IconButton
            aria-label="Generate AI summary"
            variant="ghost"
            size="sm"
            loading={isSummarizing}
            onClick={() => onSummarize(note.id)}
          >
            <LuSparkles />
          </IconButton>
        </Tooltip>
        <Tooltip content="Edit note">
          <IconButton
            aria-label="Edit note"
            variant="ghost"
            size="sm"
            onClick={() => onEdit(note)}
          >
            <LuPencil />
          </IconButton>
        </Tooltip>
        <Tooltip content="Delete note">
          <IconButton
            aria-label="Delete note"
            variant="ghost"
            size="sm"
            colorPalette="red"
            onClick={() => onDelete(note)}
          >
            <LuTrash2 />
          </IconButton>
        </Tooltip>
      </Card.Footer>
    </Card.Root>
  )
}
