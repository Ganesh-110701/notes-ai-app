import { Badge, Flex, HStack, Heading, Text, VStack } from '@chakra-ui/react'
import { LuSparkles } from 'react-icons/lu'
import { ColorModeButton } from './ui/color-mode'

export function Header() {
  return (
    <Flex align="center" justify="space-between" wrap="wrap" gap="4">
      <VStack align="start" gap="1">
        <HStack gap="2">
          <Flex
            align="center"
            justify="center"
            boxSize="9"
            borderRadius="lg"
            bgGradient="to-br"
            gradientFrom="purple.400"
            gradientTo="pink.400"
            color="white"
          >
            <LuSparkles size={18} />
          </Flex>
          <Heading size="lg" letterSpacing="-0.02em">
            AI Notes
          </Heading>
          <Badge colorPalette="purple" variant="subtle" borderRadius="full">
            demo
          </Badge>
        </HStack>
        <Text color="fg.muted" fontSize="sm">
          Notes CRUD, AI semantic search, and AI-generated summaries.
        </Text>
      </VStack>

      <ColorModeButton />
    </Flex>
  )
}
