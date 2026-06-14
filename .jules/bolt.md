## 2026-06-14 - Optimize Language Query Caching
**Learning:** When caching taxonomy datasets like Languages, using Model::all() serializes all columns and limits reuse. Using targeted selects (get(['id', 'label', 'code'])) and a unified cache key across controllers prevents memory bloat and maximizes cache hits.
**Action:** Use get(['id', 'label', 'code']) and the same cache key 'all_languages_ordered' in ProfileController and ProfileChatController.
