## 2024-05-19 - Missing Accessibility on Interactive List Items
**Learning:** Found a pattern across the discovery UI where interactable items (favorite/neutral/refuse buttons, search inputs, delete actions) use raw `<button>` elements with inline SVGs for icons but completely lack `aria-label` attributes and keyboard focus indicators (`focus-visible` ring utility classes). This makes the interface very hard to use for screen reader users or those relying on keyboard navigation.
**Action:** When working on new list components or interactive widgets, ensure all icon-only buttons include `aria-label` or `sr-only` text, hide the internal SVGs using `aria-hidden="true"`, and include explicit `focus-visible:ring` states for keyboard navigability.

## 2024-05-17 - Keyboard Accessibility with Hover Utilities
**Learning:** Using `opacity-0 group-hover:opacity-100` on interactive elements (like icon buttons) hides them completely for keyboard-only users who navigate via the Tab key, causing them to tab through invisible elements.
**Action:** Always pair `group-hover:opacity-100` with `focus-within:opacity-100` on the parent container (and `focus-within` styles on other hover states) so the elements become fully visible when a child element receives keyboard focus.

## 2026-05-18 - Alpine.js aria-expanded state handling
**Learning:** When building interactive toggles with Alpine.js (like the mobile hamburger menu), the ARIA state `aria-expanded` needs to reflect the dynamic boolean variable (e.g. `open`). However, HTML ARIA attributes require explicit string values ('true' or 'false').
**Action:** Always dynamically bind the attribute using `:aria-expanded="open.toString()"` instead of just `open` to ensure valid ARIA string values are rendered for screen readers.
## 2024-05-20 - Semantic Roles for Custom Interactive Elements
**Learning:** Turning a non-interactive element (like a `<span>`) into an interactive one via Alpine (e.g. `@dblclick`) requires more than just a `tabindex`. Without a keyboard event handler (like `@keydown.enter`) and a `role="button"`, screen reader users won't know it's actionable and keyboard users cannot trigger it. Also, applying `tabindex="0"` to generic parent containers simply to activate `focus-within` styles creates a confusing dual-focus experience for keyboard navigation.
**Action:** When making custom interactive elements, always include the semantic `role="button"`, `tabindex="0"`, and equivalent keyboard listeners (`@keydown.enter`/Space). Ensure focus rings are applied directly to the interactive element, not to purely structural parent wrappers.
