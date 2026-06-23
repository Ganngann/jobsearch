## 2024-05-20 - Prevent memory bloat in cache
**Learning:** Caching full models with large base64 fields causes memory bloat. The Employer model contains a massive logo_base64 field.
**Action:** Always use targeted select() before withCount() when caching model lists.
