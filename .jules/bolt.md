## 2026-07-25 - Laravel Eloquent withCount and select Order
**Learning:** When using `withCount()` in Laravel Eloquent, calling `select()` afterwards overwrites the select array and removes the dynamically added count attribute, breaking subsequent `orderBy` calls. Additionally, fetching models with large fields (like `logo_base64`) causes severe memory bloat when cached.
**Action:** Always place `select()` before `withCount()` and use targeted selects for models with large fields.
