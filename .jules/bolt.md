## 2026-07-22 - Targeted Selects for Cache Memory Reduction
**Learning:** Caching Eloquent queries that return entire models can severely bloat memory and cache sizes if the models contain massive fields like `logo_base64`.
**Action:** Always use targeted `select(['id', 'label'])` when querying models for UI lists or caching, explicitly bypassing heavy unneeded columns.
