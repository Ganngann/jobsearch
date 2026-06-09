## 2026-06-09 - Targeted Selects for Caching
**Learning:** Caching Eloquent models with large fields (like logo_base64 in Employer) without targeted selects causes memory bloat and large cache payloads.
**Action:** Always use select() before withCount() when caching models for lists to limit memory footprint.
