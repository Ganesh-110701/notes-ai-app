import { Button, Text } from '@chakra-ui/react'
import {
  DialogBody,
  DialogCloseTrigger,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogRoot,
  DialogTitle,
} from './ui/dialog'

export function DeleteConfirmDialog({ note, onOpenChange, onConfirm }) {
  return (
    <DialogRoot open={!!note} onOpenChange={(e) => onOpenChange(e.open)} size="sm" role="alertdialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete note?</DialogTitle>
        </DialogHeader>
        <DialogBody>
          <Text color="fg.muted">
            This will permanently delete "<Text as="span" fontWeight="semibold">{note?.title}</Text>". This
            can't be undone.
          </Text>
        </DialogBody>
        <DialogFooter>
          <Button variant="ghost" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button colorPalette="red" onClick={() => onConfirm(note)}>
            Delete
          </Button>
        </DialogFooter>
        <DialogCloseTrigger />
      </DialogContent>
    </DialogRoot>
  )
}
