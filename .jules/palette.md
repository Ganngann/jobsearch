## 2025-02-12 - Accessible Alpine.js Radio Buttons
**Learning:** When building custom radio button groups using simple `<button>` elements and Alpine.js, screen readers will not announce their selected state by default, and clicking them might submit nearby forms if `type="button"` is omitted.
**Action:** Always include `type="button"` to prevent unintended form submissions, and bind `:aria-pressed="condition"` (e.g., `:aria-pressed="type === 'feedback'"`) so screen readers correctly announce their active/selected state.

## 2025-02-12 - Alpine.js Boolean ARIA Binding
**Learning:** When dynamically binding boolean ARIA attributes in Alpine.js that must remain in the DOM when false (like `aria-expanded`), Alpine evaluates the false state by removing the attribute entirely, which can break screen reader context.
**Action:** Use `.toString()` on the Alpine condition (e.g., `:aria-expanded="open.toString()"`) to ensure the attribute is rendered as `aria-expanded="false"` rather than being removed entirely.
