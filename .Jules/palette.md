## 2024-05-19 - Missing Accessibility on Interactive List Items
**Learning:** Found a pattern across the discovery UI where interactable items (favorite/neutral/refuse buttons, search inputs, delete actions) use raw `<button>` elements with inline SVGs for icons but completely lack `aria-label` attributes and keyboard focus indicators (`focus-visible` ring utility classes). This makes the interface very hard to use for screen reader users or those relying on keyboard navigation.
**Action:** When working on new list components or interactive widgets, ensure all icon-only buttons include `aria-label` or `sr-only` text, hide the internal SVGs using `aria-hidden="true"`, and include explicit `focus-visible:ring` states for keyboard navigability.

## 2024-05-17 - Keyboard Accessibility with Hover Utilities
**Learning:** Using `opacity-0 group-hover:opacity-100` on interactive elements (like icon buttons) hides them completely for keyboard-only users who navigate via the Tab key, causing them to tab through invisible elements.
**Action:** Always pair `group-hover:opacity-100` with `focus-within:opacity-100` on the parent container (and `focus-within` styles on other hover states) so the elements become fully visible when a child element receives keyboard focus.
