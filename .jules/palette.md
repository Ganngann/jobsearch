## 2024-05-20 - Non-interactive element accessibility

**Learning:** When styling non-interactive elements like `div`s as clickable components using Alpine.js (`@click`), they must be made keyboard accessible to conform to WCAG guidelines, as they do not natively receive focus or respond to keyboard events.
**Action:** Always add `role="button"`, `tabindex="0"`, keydown bindings (`@keydown.enter.prevent`, `@keydown.space.prevent`), and `focus-visible` Tailwind classes to any non-interactive element that acts as a button.
