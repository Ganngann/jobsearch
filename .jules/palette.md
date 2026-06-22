## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.

## 2026-06-22 - SVG Accessibility in Icon Buttons
**Learning:** Decorative SVGs inside icon-only buttons can be read by screen readers unnecessarily, causing noise.
**Action:** Always add `aria-hidden="true"` to inner `<svg>` elements to optimize the screen reader experience by ensuring it relies entirely on the button's `aria-label`.
