import { Button, HStack, Input } from '@chakra-ui/react'
import { useState } from 'react'
import { LuSearch, LuX } from 'react-icons/lu'
import { InputGroup } from './ui/input-group'

export function SearchBar({ value, onChange, onSearch, onClear, isSearching }) {
  const [localValue, setLocalValue] = useState(value)

  const submit = (e) => {
    e.preventDefault()
    onChange(localValue)
    onSearch(localValue)
  }

  return (
    <form onSubmit={submit}>
      <HStack gap="2">
        <InputGroup flex="1" startElement={<LuSearch />}>
          <Input
            placeholder="Semantic search — try “money savings” or “staying fit”…"
            value={localValue}
            onChange={(e) => setLocalValue(e.target.value)}
            size="lg"
          />
        </InputGroup>
        <Button type="submit" size="lg" colorPalette="purple">
          Search
        </Button>
        {isSearching && (
          <Button
            type="button"
            size="lg"
            variant="ghost"
            onClick={() => {
              setLocalValue('')
              onClear()
            }}
          >
            <LuX /> Clear
          </Button>
        )}
      </HStack>
    </form>
  )
}
