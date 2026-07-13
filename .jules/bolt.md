## 2026-07-13 - Targeted Selects with Aggregates
**Learning:** When using aggregate functions like `withCount()` on models containing large data fields (e.g., base64 images), fetching all columns causes severe memory bloat, especially when caching the results.
**Action:** Always use targeted `select(['id', 'label'])` placed *before* the aggregate method to reduce payload without overwriting the aggregate subquery.
