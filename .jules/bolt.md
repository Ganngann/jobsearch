## 2026-06-18 - Optimization of cache payload size
**Learning:** The 'Employer' model contains a 'logo_base64' field that can be massive. Storing all fields of the model via Cache::remember() bloats memory and Redis/SQLite payload. This applies to UI filters mapping over lists too, creating Out-Of-Memory issues.
**Action:** Always use targeted select() (e.g., select(['id', 'label'])) on Eloquent queries fetched for cached sidebar lists to prevent serializing unnecessary large payloads like longText or base64 columns.
