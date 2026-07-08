## 2024-05-19 - Missing Accessibility on Interactive List Items
**Learning:** Found a pattern across the discovery UI where interactable items (favorite/neutral/refuse buttons, search inputs, delete actions) use raw `<button>` elements with inline SVGs for icons but completely lack `aria-label` attributes and keyboard focus indicators (`focus-visible` ring utility classes). This makes the interface very hard to use for screen reader users or those relying on keyboard navigation.
**Action:** When working on new list components or interactive widgets, ensure all icon-only buttons include `aria-label` or `sr-only` text, hide the internal SVGs using `aria-hidden="true"`, and include explicit `focus-visible:ring` states for keyboard navigability.

## 2024-05-17 - Keyboard Accessibility with Hover Utilities
**Learning:** Using `opacity-0 group-hover:opacity-100` on interactive elements (like icon buttons) hides them completely for keyboard-only users who navigate via the Tab key, causing them to tab through invisible elements.
**Action:** Always pair `group-hover:opacity-100` with `focus-within:opacity-100` on the parent container (and `focus-within` styles on other hover states) so the elements become fully visible when a child element receives keyboard focus.

## 2026-05-18 - Alpine.js aria-expanded state handling
**Learning:** When building interactive toggles with Alpine.js (like the mobile hamburger menu), the ARIA state `aria-expanded` needs to reflect the dynamic boolean variable (e.g. `open`). However, HTML ARIA attributes require explicit string values ('true' or 'false').
**Action:** Always dynamically bind the attribute using `:aria-expanded="open.toString()"` instead of just `open` to ensure valid ARIA string values are rendered for screen readers.

## 2024-07-08 - Missing Screen Reader Labels on Filter Inputs
**Learning:** The dashboard uses raw inputs for dynamic filtering without providing screen-reader labels via `sr-only` classes, breaking context for AT users. Furthermore, interactive clear filter icons lacked focus-visible states and aria labels, breaking keyboard accessibility.
**Action:** When working on form inputs without visual labels, ensure a `<label class="sr-only">` is linked via `id`. Apply `focus-visible:ring` states and `aria-label` to interactive icon buttons.
