## 2026-07-01 - Caching Payloads
**Learning:** Caching models with longText fields using get() without specific columns causes memory bloat. When combined with withCount(), select() must be placed first.
**Action:** Always use targeted selects (e.g., select(['id', 'label'])) before withCount() when caching models for lists.
