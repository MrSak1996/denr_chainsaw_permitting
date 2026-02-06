import type { Directive } from 'vue'


export const lettersOnlyUppercase: Directive = {
  mounted(el) {
    el.addEventListener('keydown', (event: KeyboardEvent) => {
      const key = event.key

      // Allow control keys
      if (
        key === 'Backspace' ||
        key === 'Delete' ||
        key === 'Tab' ||
        key === 'ArrowLeft' ||
        key === 'ArrowRight' ||
        key === 'Home' ||
        key === 'End'
      ) {
        return
      }

      // Allow only letters and spaces
      if (!/^[a-zA-Z\s]$/.test(key)) {
        event.preventDefault()
      }
    })

    // Convert to uppercase on input WITHOUT dispatching a new event
    el.addEventListener('input', () => {
      el.value = el.value.toUpperCase()
    })
  }
}
export const lettersNumbersDashUppercase: Directive = {
  mounted(el) {
    el.addEventListener('keydown', (event: KeyboardEvent) => {
      const key = event.key

      // Allow control keys
      if (
        key === 'Backspace' ||
        key === 'Delete' ||
        key === 'Tab' ||
        key === 'ArrowLeft' ||
        key === 'ArrowRight' ||
        key === 'Home' ||
        key === 'End'
      ) {
        return
      }

      // Allow letters, numbers, space, and dash
      if (!/^[a-zA-Z0-9\s-]$/.test(key)) {
        event.preventDefault()
      }
    })

    // Convert to uppercase on input WITHOUT dispatching another event
    el.addEventListener('input', () => {
      el.value = el.value.toUpperCase()
    })
  }
}

