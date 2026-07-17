## 2026-07-17 - Targeted Selects with count()
**Learning:** When combining targeted selects with aggregate functions like `withCount()` on Eloquent models (especially those with massive fields like `logo_base64`), placing the `select()` clause before `withCount()` ensures the aggregate subquery is correctly appended and drastically reduces memory bloat.
**Action:** Always use targeted selects on models with large unused payload fields when building lists or caches, ensuring `select` precedes `withCount`.
