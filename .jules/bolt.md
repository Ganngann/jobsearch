## 2026-06-08 - Optimize Taxonomy Caching
**Learning:** Unifying cache keys and using targeted selects (instead of Model::all) avoids serializing unnecessary data and maximizes cache hits.
**Action:** Always use targeted selects and unified cache keys when caching taxonomy datasets.
