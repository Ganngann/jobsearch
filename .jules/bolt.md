## 2026-06-27 - Cache memory bloat with Employer logo
**Learning:** Caching models with logo_base64 like Employer causes severe memory bloat because PDO buffers everything. withCount also works best when select() is called first so aggregate subqueries are not overwritten.
**Action:** Always use targeted selects (e.g. select(['id', 'label'])) before withCount() when querying models with heavy payloads for caching.
