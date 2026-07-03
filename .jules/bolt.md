## 2026-07-03 - Targeted Select with Aggregate Count
**Learning:** When combining targeted selects (e.g., `select(['id', 'label'])`) with aggregate functions like `withCount()` on Eloquent models to reduce memory payload, always place the `select()` clause before `withCount()`. If placed after, the aggregate subquery may be overwritten or ignored.
**Action:** Ensure `select()` comes before `withCount()` when querying.
