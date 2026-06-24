## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-06-24 - Keyboard Accessibility and Focus states on Dynamic Buttons
**Learning:** Found that some action buttons (like add/refuse job metiers) lacked proper aria labels, aria hidden tags on their inner svgs, and focus rings which makes them harder to use for keyboard-only users.
**Action:** Add focus-visible classes for keyboard focus styling, aria-hidden for icons inside buttons, aria-labels for the buttons themselves, and include focus states when using hover-based opacity reveals.
