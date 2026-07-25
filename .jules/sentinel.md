## 2025-05-18 - [CSRF Vulnerability in Admin Match Clearing Route]
**Vulnerability:** A route performing a destructive, state-changing action (`clearAllMatches`) was accessible via a `GET` request (`/admin/matching/clear-get`).
**Learning:** This breaks RESTful principles and creates a Cross-Site Request Forgery (CSRF) vulnerability. An admin user could be tricked into clicking a link, inadvertently deleting database records, because `GET` requests do not enforce CSRF token validation.
**Prevention:** Always use `POST`, `PUT`, `PATCH`, or `DELETE` for state-changing routes to ensure Laravel's built-in CSRF protection is applied. Remove shortcut `GET` routes for these actions.

## 2026-05-17 - Fix Wildcard Injection in LIKE queries
**Vulnerability:** User input containing wildcard characters (%, _) was directly injected into SQL LIKE clauses, enabling a database Denial of Service attack by forcing wide scans.
**Learning:** Using backslashes ('\') to escape wildcards in Laravel's whereRaw is not cross-database compatible. SQLite interprets it correctly with an ESCAPE clause, but MySQL interprets the backslash as a string escape, breaking the query syntax.
**Prevention:** Use a database-agnostic escape character (like '=') in whereRaw (e.g., `whereRaw("column LIKE ? ESCAPE '='", ["%{$escaped}%"])`) and escape the user input with `str_replace(['=', '%', '_'], ['==', '=%', '=_'], $input)`.

## 2026-05-19 - Fix XSS Vulnerability in Blade Templates
**Vulnerability:** Unescaped variables containing HTML content (like `$jobOffer->description`) were rendered using Blade's `{!! !!}` syntax without sanitization, leading to a Cross-Site Scripting (XSS) vulnerability if the data is maliciously crafted.
**Learning:** While using `nl2br(e($content))` is safe for plain text, complex HTML needs proper sanitization.
**Prevention:** Replace all unescaped `{!! $variable !!}` usages with the custom `@purify($variable)` Blade directive (which uses HTMLPurifier) when displaying external or user-generated HTML content.
## 2026-07-25 - XSS in Alpine.js Bindings
**Vulnerability:** XSS via addslashes in Blade attributes used with Alpine.js.
**Learning:** Using addslashes in double-quoted HTML attributes for Alpine.js x-show/x-data bindings can lead to XSS because addslashes does not escape HTML entities, allowing quote breakout.
**Prevention:** Use Laravel's @js() directive inside single-quoted HTML attributes to safely encode data for JavaScript consumption.
