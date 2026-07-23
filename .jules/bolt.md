## 2026-07-23 - Targeted Selects with count
**Learning:** Eloquent's `withCount()` gets overwritten if `select()` is chained after it, breaking dynamic attributes like `job_offers_count`. Furthermore, fetching full models with heavy base64 strings (like Employer logos) causes significant cache/memory bloat.
**Action:** Always place `select()` before `withCount()` and restrict fetched columns to only what's required by the view when caching aggregates.
