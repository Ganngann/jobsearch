## 2024-05-16 - N+1 query problem in MatchingService
**Learning:** Found N+1 queries in `MatchingService::calculatePreScore` due to dynamic relationship calls (`$jobOffer->permits()->...->get()`) instead of using pre-loaded relationships (`$jobOffer->permits->where(...)`).
**Action:** Replace `()->where...()->get()` calls with collection methods on preloaded properties (`->where(...)`) to utilize eager loading when matching chunks of jobs.
