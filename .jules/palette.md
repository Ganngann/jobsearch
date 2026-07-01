## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-07-01 - Expanded Keyboard Accessibility for Hover-Revealed Actions
**Learning:** When using Tailwind's `opacity-0 group-hover:opacity-100` for tooltips or actions, adding just `focus-within:opacity-100` might not be enough depending on the element's focusability. We must ensure `group-focus-within:opacity-100` is applied so tabbing through elements within the group properly reveals the hidden tooltip or action.
**Action:** Always include `group-focus-within:opacity-100` alongside `focus-within:opacity-100` for `group-hover:opacity-100` elements.
