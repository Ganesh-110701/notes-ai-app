import { Button, Input, Stack, Textarea } from '@chakra-ui/react'
import { useEffect, useState } from 'react'
import {
  DialogBody,
  DialogCloseTrigger,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogRoot,
  DialogTitle,
} from './ui/dialog'
import { Field } from './ui/field'

const emptyForm = { title: '', content: '' }

// Handles both create and edit - pre-fills the form when `note` is passed in.
export function NoteFormDialog({ isOpen, onOpenChange, note, onSubmit }) {
  const [form, setForm] = useState(emptyForm)
  const [errors, setErrors] = useState({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    if (isOpen) {
      setForm(note ? { title: note.title, content: note.content } : emptyForm)
      setErrors({})
    }
  }, [isOpen, note])

  const handleSubmit = async (e) => {
    e.preventDefault()
    setIsSubmitting(true)
    setErrors({})
    try {
      await onSubmit(form)
      onOpenChange(false)
    } catch (error) {
      if (error.status === 422) {
        setErrors(error.errors)
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <DialogRoot open={isOpen} onOpenChange={(e) => onOpenChange(e.open)} size="md">
      <DialogContent>
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>{note ? 'Edit note' : 'New note'}</DialogTitle>
          </DialogHeader>
          <DialogBody>
            <Stack gap="4">
              <Field label="Title" invalid={!!errors.title} errorText={errors.title?.[0]}>
                <Input
                  autoFocus
                  placeholder="Give your note a title"
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                />
              </Field>
              <Field label="Content" invalid={!!errors.content} errorText={errors.content?.[0]}>
                <Textarea
                  placeholder="Write your note..."
                  rows={6}
                  value={form.content}
                  onChange={(e) => setForm({ ...form, content: e.target.value })}
                />
              </Field>
            </Stack>
          </DialogBody>
          <DialogFooter>
            <Button variant="ghost" onClick={() => onOpenChange(false)} type="button">
              Cancel
            </Button>
            <Button colorPalette="purple" type="submit" loading={isSubmitting}>
              {note ? 'Save changes' : 'Create note'}
            </Button>
          </DialogFooter>
          <DialogCloseTrigger />
        </form>
      </DialogContent>
    </DialogRoot>
  )
}
