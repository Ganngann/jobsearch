## 2026-07-15 - Targeted Selects with Aggregates
**Learning:** When combining targeted selects (e.g., `select(['id', 'label'])`) with aggregate functions like `withCount()` on Eloquent models to reduce memory payload, always place the `select()` clause before `withCount()` to ensure the aggregate subquery is correctly appended rather than overwritten.
**Action:** Use `select()` before `withCount()` when optimizing Eloquent queries.
