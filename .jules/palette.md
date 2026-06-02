## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.

## 2024-05-20 - Dynamic ARIA Labels for Stateful Icon Buttons
**Learning:** Icon-only buttons that toggle application state via Alpine.js (e.g., Favorites, Blacklist) frequently rely solely on dynamic `:title` attributes or static SVG icons, which fails to accurately announce the available action to screen readers.
**Action:** For stateful icon buttons managed by Alpine.js, dynamically bind the `:aria-label` attribute (e.g., `:aria-label="state ? 'Action to undo' : 'Action to do'"`) to match the `:title` to ensure screen readers accurately announce the available action based on the current component state.
