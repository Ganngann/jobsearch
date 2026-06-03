## 2026-06-03 - Optimize Cached Database Query Selects
**Learning:** When retrieving frequently accessed, large collections (like 'top' lists) into a Laravel cache, omitting unneeded model attributes significantly reduces the cached payload size and improves database retrieval speed. However, one must verify the template consumption of these models to ensure required attributes are not inadvertently omitted from the `select` array.
**Action:** Always append `->select(['id', 'label', ...])` on cacheable collection queries after verifying which attributes are actively used in the Blade views.
