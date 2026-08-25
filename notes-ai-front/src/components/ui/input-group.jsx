import { Group, InputAddon, InputElement } from '@chakra-ui/react'
import * as React from 'react'

export const InputGroup = React.forwardRef(function InputGroup(props, ref) {
  const {
    startElement,
    startElementProps,
    endElement,
    endElementProps,
    children,
    startAddon,
    startAddonProps,
    endAddon,
    endAddonProps,
    startOffset = '6px',
    endOffset = '6px',
    ...rest
  } = props

  const child = React.Children.only(children)

  return (
    <Group ref={ref} {...rest}>
      {startAddon && <InputAddon {...startAddonProps}>{startAddon}</InputAddon>}
      {startElement && (
        <InputElement pointerEvents='none' {...startElementProps}>
          {startElement}
        </InputElement>
      )}
      {React.cloneElement(child, {
        ...(startElement &&
          !startAddon && {
            ps: `calc(var(--input-height) - ${startOffset})`,
          }),
        ...(endElement &&
          !endAddon && {
            pe: `calc(var(--input-height) - ${endOffset})`,
          }),
        ...children.props,
      })}
      {endElement && (
        <InputElement placement='end' {...endElementProps}>
          {endElement}
        </InputElement>
      )}
      {endAddon && <InputAddon {...endAddonProps}>{endAddon}</InputAddon>}
    </Group>
  )
})
