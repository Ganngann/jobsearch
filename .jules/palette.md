## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-06-15 - Adding ARIA labels to stateful icon buttons
**Learning:** When using Alpine.js for interactive toggles, binding dynamic `:aria-label` attributes based on state ensures screen readers accurately announce available actions.
**Action:** Always map Alpine state variables (e.g., `isPreferred`, `status`) to explicit accessible labels using the same logic as the visual `title` attribute.
