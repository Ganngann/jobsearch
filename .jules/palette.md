## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-06-08 - Adding ARIA labels to Alpine.js Loop Action Buttons
**Learning:** In the CV components managed by Alpine.js loops, many action buttons (e.g., delete, add) are icon-only. They are missing descriptive ARIA labels, rendering them inaccessible to screen reader users.
**Action:** When building interactive components or icon-only buttons inside loops, always ensure proper accessibility by providing clear, context-specific `aria-label` and `title` attributes (e.g., `aria-label="Supprimer la langue"`).
