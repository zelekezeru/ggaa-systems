---
name: portal-404-debug
user-invocable: true
description: "Use when diagnosing a 404 or route failure for the client portal dashboard (/portal/dashboard) in this Laravel application. Guides through route, middleware, auth/role, and local server checks."
---

# Client Portal 404 Debug Skill

## What this skill does

- Identifies whether `/portal/dashboard` is missing from the app route configuration
- Verifies middleware and role restrictions for the client portal route
- Checks auth status, role membership, and route cache issues
- Suggests the correct Laravel commands and diagnostics to resolve a 404 on the client portal page

## When to use

- You see `Failed to load resource: the server responded with a status of 404` for `http://127.0.0.1:8000/portal/dashboard`
- The app should render a client portal dashboard but instead fails to load
- You need a repeatable workflow for Laravel route and auth debugging in this repo

## Steps

1. Confirm the route exists in `routes/web.php`:
   - Look for `Route::get('/portal/dashboard', [ClientDashboardController::class, 'index'])`
   - Ensure it is inside the `Route::middleware(['auth', 'role:Client'])` group

2. Confirm the current request is a GET request and that the browser is hitting the right URL:
   - Use browser DevTools Network tab
   - Check if the request is for `/portal/dashboard` and not an asset or wrong path

3. Verify the route is present in Laravel’s route list:
   - Run `php artisan route:list --name=client.dashboard`
   - Confirm method is `GET`, URI is `portal/dashboard`, and middleware includes `auth` and `role:Client`

4. Check user authentication and role membership:
   - Confirm the current portal user is authenticated
   - Confirm the user has the `Client` role
   - Verify the `role` middleware is not returning 404 for unauthorized access in this app

5. Check controller and Inertia rendering:
   - Inspect `app/Http/Controllers/Client/DashboardController.php`
   - Confirm `index()` returns `Inertia::render('Client/Dashboard', [...])`

6. Clear cached route/config state if the route exists but still returns 404:
   - `php artisan route:clear`
   - `php artisan config:clear`
   - `php artisan cache:clear`

7. Confirm local server and base URL alignment:
   - Ensure `APP_URL` matches the served address, such as `http://127.0.0.1:8000`
   - Ensure the dev server is running and `php artisan serve` or equivalent is using the right host/port

8. Inspect the actual response body for the 404:
   - In Network tab, view response text
   - Look for framework errors, route mismatch info, or redirect attempts to login

## Common fixes

- Add or restore the `client.dashboard` route if missing
- Update the user role if the current account is not a client
- Clear route/cache state after changing routes or middleware
- Use the correct local host and port when developing

## Example prompts

- `Help me debug why /portal/dashboard returns 404 in this Laravel app`
- `Check the client portal route and auth middleware for the dashboard page`
- `Verify the portal dashboard route exists and identify why the browser fails to load it`

## Notes

This skill is workspace-scoped and designed for this repository’s Laravel route structure. It is a diagnostic workflow, not a code patch by itself.
