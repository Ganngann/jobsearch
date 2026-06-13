## 2026-06-13 - Optimize cache payload for Employer and Metier
**Learning:** The Employer model contains a large logo_base64 column. Using Model::get() without a targeted select caches the entire model, causing significant memory bloat and potential Out-Of-Memory errors. Additionally, when using select() with aggregate functions like withCount(), select() must be placed before withCount() to avoid overwriting the aggregate subquery.
**Action:** Always use targeted select(['id', 'label']) before withCount() when caching models for lists.
