## 2026-07-18 - Prevent memory bloat with targeted selects before withCount()
**Learning:** Eloquent models with large fields (like Employer's logo_base64) cause severe memory bloat when fully loaded for caching. However, when combining targeted selects with aggregate functions like withCount(), the select() clause must be placed before withCount() to ensure the aggregate subquery is appended rather than overwritten.
**Action:** Always use targeted selects (e.g., select(['id', 'label'])) before aggregate functions like withCount() when fetching records for UI lists to minimize memory payload.
