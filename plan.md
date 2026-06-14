1. Modify `app/Http/Controllers/ProfileController.php` to use targeted selects and a unified cache key instead of `Language::all()`.
2. Modify `app/Http/Controllers/ProfileChatController.php` to include the `code` column in its cached language query to match `ProfileController`, keeping the unified cache key.
3. Verify changes using `cat` and `php -l`.
4. Run full test suite using `composer test && pnpm test`.
5. Complete pre-commit steps to ensure proper testing, verification, review, and reflection are done.
6. Create `.jules/bolt.md` to log the performance learning.
7. Submit the PR.
