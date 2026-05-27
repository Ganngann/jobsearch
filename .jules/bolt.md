## 2024-05-27 - OOM avoidance with chunkById and selects
**Learning:** In Laravel, calling `->chunkById()` buffers models to loop over. If the models have large text attributes (like embeddings, descriptions, etc) it can still exhaust PHP memory if we just want to pluck their IDs or check a small property.
**Action:** When using `chunkById` or chunking in general to dispatch jobs with only IDs, always use `->select('id')` to prevent memory exhaustion by fetching only the IDs we need for dispatching.

## 2024-05-27 - N+1 in Loops
**Learning:** In `triggerTopAiAnalysis`, iterating over `topMatches` and accessing `$match->jobOffer` without eager loading triggers N+1 queries.
**Action:** Use `->with('jobOffer')` to prevent N+1 queries when accessing relationships inside a loop.
