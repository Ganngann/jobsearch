## 2024-06-30 - Optimize Cache Payloads
**Learning:** When combining targeted selects with aggregate functions like `withCount()` on Eloquent models to reduce memory payload (especially with large fields like `logo_base64`), always place the `select()` clause before `withCount()` to ensure the aggregate subquery is correctly appended rather than overwritten.
**Action:** Use `select(['id', 'label'])` before `withCount()` to prevent Out-Of-Memory errors when caching massive datasets.
