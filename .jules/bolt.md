## 2026-07-02 - Targeted Selects for Cache Optimization
**Learning:** Caching full Eloquent models with large fields (like logo_base64) causes memory bloat. Targeted select() must precede aggregate functions like withCount().
**Action:** Always use targeted select(['id', 'label']) before aggregate functions when querying data for cache.
