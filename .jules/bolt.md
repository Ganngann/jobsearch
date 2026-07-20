## 2026-07-20 - Prevent memory bloat on Employer cache queries
**Learning:** The Employer model contains a potentially massive logo_base64 field. Loading entire Employer models for lists or caching causes severe memory bloat.
**Action:** Always use targeted selects (e.g., select(['id', 'label'])) when fetching Employer records for lists or caching, as the sidebar views only depend on id, label, and the aggregated job_offers_count.
