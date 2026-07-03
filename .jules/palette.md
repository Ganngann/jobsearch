## 2024-05-19 - Keyboard Accessibility for Hover-Revealed Actions
**Learning:** In the CV components, there are many action buttons (e.g., delete, add) that are hidden by default using `opacity-0` and revealed on hover using `group-hover:opacity-100`. This hides the actions from keyboard users (tabbing) because the elements don't become visible when focused.
**Action:** When hiding elements visually with Tailwind's `opacity-0 group-hover:opacity-100`, always pair it with `focus-within:opacity-100` (on the parent group) or `focus:opacity-100` (on the element itself) to ensure they are visible when receiving keyboard focus.
## 2026-07-03 - Playwright Authentication Buttons
**Learning:** The default Laravel Breeze local authentication views use English strings (e.g., 'Log in') instead of French ('Se connecter'), which causes Playwright locator timeouts if the latter is assumed.
**Action:** Use `page.get_by_role('button', name='Log in')` when authenticating in verification scripts on this codebase.
