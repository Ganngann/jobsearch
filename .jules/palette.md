## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.

## 2024-06-29 - Keyboard Accessibility for Hover-Revealed Tooltips
**Learning:** Tooltips and elements hidden with `opacity-0 group-hover:opacity-100` are inaccessible to keyboard users navigating via Tab, as focusing the element or its parent does not trigger visibility.
**Action:** Always append `group-focus-within:opacity-100` (and/or `focus-within:opacity-100`, `focus:opacity-100`) to elements that use `group-hover:opacity-100` to reveal content on hover, ensuring they are also revealed on keyboard focus.

## 2024-06-29 - Keyboard Accessibility for Hover Animation Transforms
**Learning:** When using Tailwind classes to reveal elements on hover using transforms (e.g., `transform translate-x-4 group-hover:translate-x-0`), keyboard users who focus the element will trigger visibility changes but won't get the position changes without equivalent focus classes.
**Action:** When applying Tailwind hover animation transforms (like `group-hover:translate-x-0` or `group-hover:translate-y-0`), ensure to also apply equivalent keyboard focus states (e.g., `group-focus-within:translate-x-0` and `group-focus:translate-x-0`) so animations behave correctly for keyboard users navigating via Tab.
