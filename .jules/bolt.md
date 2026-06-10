## 2026-06-10 - Targeted Selects with withCount
**Learning:** Place select() before withCount() to prevent overwriting aggregates and avoid memory bloat.
**Action:** Always use targeted selects before aggregates when caching.
