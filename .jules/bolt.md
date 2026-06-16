## 2026-06-16 - Targeted Selects in Cache
**Learning:** Caching models with massive fields (like logo_base64) without targeted selects causes memory bloat.
**Action:** Always place targeted select() clauses before aggregate functions like withCount() when caching.
