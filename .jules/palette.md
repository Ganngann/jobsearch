## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## $(date +%Y-%m-%d) - Focus visibility for on-hover elements
**Learning:** In Tailwind, components that rely on `opacity-0 group-hover:opacity-100` are completely inaccessible to keyboard users because tabbing into them doesn't trigger the hover state.
**Action:** Always pair `group-hover:opacity-100` with `group-focus:opacity-100` or `focus-within:opacity-100` to ensure these elements become visible and readable when keyboard navigation passes through them.
