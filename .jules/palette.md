## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-06-30 - Dynamic ARIA Labels on Alpine.js Toggles
**Learning:** When using Alpine.js to create a state-toggled button (like a floating action button), relying solely on a static `title` is insufficient for screen readers as the action's intent changes when toggled.
**Action:** Use Alpine's dynamic binding for accessibility attributes (e.g., `:aria-label="open ? 'Fermer' : 'Donne ton avis'"`) to ensure screen readers accurately announce the available action based on the component's current state.
