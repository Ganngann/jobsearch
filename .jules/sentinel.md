## 2025-05-18 - [CSRF Vulnerability in Admin Match Clearing Route]
**Vulnerability:** A route performing a destructive, state-changing action (`clearAllMatches`) was accessible via a `GET` request (`/admin/matching/clear-get`).
**Learning:** This breaks RESTful principles and creates a Cross-Site Request Forgery (CSRF) vulnerability. An admin user could be tricked into clicking a link, inadvertently deleting database records, because `GET` requests do not enforce CSRF token validation.
**Prevention:** Always use `POST`, `PUT`, `PATCH`, or `DELETE` for state-changing routes to ensure Laravel's built-in CSRF protection is applied. Remove shortcut `GET` routes for these actions.
