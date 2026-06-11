## 2026-06-11 - Memory Bloat with Employer Model
**Learning:** The Employer model contains a large logo_base64 field. Caching it without targeted selects causes memory bloat. Also, aggregate functions like withCount() need to be appended after the select().
**Action:** Always use targeted selects (e.g., ->select(['id', 'label'])) before withCount() when caching Employer records.
