## 2026-06-06 - Optimize Dashboard Taxonomy Queries Payload
**Learning:** When combining targeted selects with aggregate functions like `withCount()` on Eloquent models to reduce memory payload (e.g., for caching), always place the `select()` clause before `withCount()` to ensure the aggregate subquery is correctly appended rather than overwritten.
**Action:** Use targeted selects like `select(['id', 'label'])` before `withCount()` when caching taxonomy datasets to minimize cache size.
