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
## 2026-06-11 - Fix CSRF Bypass via GET method on State-Changing Route
**Vulnerability:** A state-changing operation (`/vector-sync`) was allowed to be accessed via GET requests due to `Route::match(['get', 'post'])` in `routes/web.php`, which completely bypasses Laravel's CSRF protection.
**Learning:** The `VerifyCsrfToken` middleware only validates non-read verbs (POST, PUT, PATCH, DELETE). Allowing GET requests for state-changing routes nullifies the protection.
**Prevention:** State-changing routes must strictly use POST, PUT, PATCH, or DELETE methods. Never use `Route::match` with `get` alongside state-changing methods unless explicitly separated with different logic.
