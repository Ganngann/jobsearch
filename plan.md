1. **Fix XSS in `resources/views/profile/partials/update-profile-information-form.blade.php`**
   - Replace `x-data="{ ... }"` with `x-data='{ ... }'`
   - Replace `'{{ addslashes($user->headline) }}'` with `@js($user->headline)`
2. **Verify changes to `resources/views/profile/partials/update-profile-information-form.blade.php`**
   - Run `cat resources/views/profile/partials/update-profile-information-form.blade.php | grep -C 5 x-data`
3. **Fix XSS in `resources/views/dashboard/partials/employer-filter.blade.php`**
   - Replace `x-show="employerSearch === '' || '{{ strtolower(addslashes($employer->label)) }}'.includes(employerSearch.toLowerCase())"` with `x-show='employerSearch === "" || @js(strtolower($employer->label)).includes(employerSearch.toLowerCase())'`
4. **Verify changes to `resources/views/dashboard/partials/employer-filter.blade.php`**
   - Run `cat resources/views/dashboard/partials/employer-filter.blade.php | grep -C 3 x-show`
5. **Fix XSS in `resources/views/dashboard/partials/metier-filter.blade.php`**
   - Replace `x-show="metierSearch === '' || '{{ strtolower(addslashes($metier->label)) }}'.includes(metierSearch.toLowerCase())"` with `x-show='metierSearch === "" || @js(strtolower($metier->label)).includes(metierSearch.toLowerCase())'`
6. **Verify changes to `resources/views/dashboard/partials/metier-filter.blade.php`**
   - Run `cat resources/views/dashboard/partials/metier-filter.blade.php | grep -C 3 x-show`
7. **Add Journal Entry in `.jules/sentinel.md`**
   - Run `echo -e "## $(date +%Y-%m-%d) - XSS via addslashes in Alpine bindings\n**Vulnerability:** XSS occurs when passing data to Alpine.js bindings using \`addslashes\` instead of proper JSON encoding.\n**Learning:** \`addslashes\` does not escape HTML entities or single quotes correctly within unquoted contexts or when decoded by the browser, allowing XSS payload execution.\n**Prevention:** Always use Laravel's \`@js()\` directive for passing PHP variables to JavaScript/Alpine.js. Additionally, ensure the HTML attribute containing \`@js()\` is wrapped in single quotes (e.g., \`x-data='...'\`) to prevent premature attribute termination." >> .jules/sentinel.md`
8. **Verify Journal Entry**
   - Run `cat .jules/sentinel.md`
9. **Run the full test suite**
   - Run `composer test && pnpm test:js`
10. **Complete pre-commit steps to ensure proper testing, verification, review, and reflection are done.**
11. **Submit PR**
    - Submit PR with title "🛡️ Sentinel: [HIGH] Fix XSS vulnerability in Alpine.js bindings" and required description.
