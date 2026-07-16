## 2026-07-16 - Memory Bloat with Aggregate Functions
**Learning:** When combining targeted selects with aggregate functions like `withCount()` on Eloquent models to reduce memory payload (e.g., for caching, especially with large fields like `logo_base64` in `Employer`), the `select()` clause must be placed before `withCount()` to ensure the aggregate subquery is correctly appended rather than overwritten.
**Action:** Always place targeted `select(['id', 'label'])` before `withCount()` when optimizing Eloquent queries.
