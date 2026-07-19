## 2026-07-19 - Targeted Selects for Large Payloads
**Learning:** When fetching models with massive payload columns (like base64 images) for lists or caching, always use targeted selects (e.g., `select(['id', 'label'])`).
**Action:** Audit queries that load potentially large text/blob columns and ensure they only fetch what is actually needed for the view/logic to prevent memory exhaustion and cache bloat.
