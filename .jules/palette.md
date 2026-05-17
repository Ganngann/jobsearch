## 2024-05-17 - Keyboard Accessibility with Hover Utilities
**Learning:** Using `opacity-0 group-hover:opacity-100` on interactive elements (like icon buttons) hides them completely for keyboard-only users who navigate via the Tab key, causing them to tab through invisible elements.
**Action:** Always pair `group-hover:opacity-100` with `focus-within:opacity-100` on the parent container (and `focus-within` styles on other hover states) so the elements become fully visible when a child element receives keyboard focus.
