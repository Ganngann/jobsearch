## 2026-06-20 - Prevent Memory Bloat in Employer Caching
**Learning:** The Employer model contains a massive logo_base64 field. Fetching all columns into cache for UI dropdowns causes significant memory bloat.
**Action:** When caching taxonomy datasets, use targeted selects (e.g., select(['id', 'label'])) before aggregate functions like withCount() to prevent serializing unnecessary large columns into cache.
