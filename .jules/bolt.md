## 2026-06-17 - Select Before withCount on Employer Model
**Learning:** The Employer model contains a large logo_base64 field. Fetching all columns inside Cache::remember can cause memory bloat. Combining select() with withCount() requires select() to come first so the aggregate subquery is correctly appended rather than overwritten.
**Action:** Always use targeted selects (e.g., select(['id', 'label'])) before aggregate methods like withCount() when caching Employer records.
