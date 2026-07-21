## 2024-07-21 - Massive fields in Employer model
**Learning:** The `Employer` model contains a potentially massive `logo_base64` field that can cause severe memory bloat if loaded unnecessarily.
**Action:** Always use targeted selects (e.g., `select(['id', 'label'])`) when fetching Employer records for lists or caching.
