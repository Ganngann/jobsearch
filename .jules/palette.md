## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-06-28 - Accessible Alpine Radio Groups
**Learning:** When building custom radio button groups using simple buttons and Alpine.js (e.g. for feedback types), they lack native semantic state. Screen readers won't announce which option is selected.
**Action:** Always add `:aria-pressed="condition"` to these buttons so assistive technologies announce their active state properly.
