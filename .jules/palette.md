## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-07-22 - Accessible Interactive Divs
**Learning:** When using inherently non-interactive elements like `<div>` as clickable cards via Alpine.js, keyboard users cannot interact with them, and screen readers lack context.
**Action:** Always provide full accessibility by adding `role="button"`, `tabindex="0"`, equivalent keyboard event handlers (`@keydown.enter`, `@keydown.space.prevent`), and `focus-visible` styles.
