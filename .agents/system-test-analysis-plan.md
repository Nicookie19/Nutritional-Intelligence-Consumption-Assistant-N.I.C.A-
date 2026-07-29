# System-Level Page Test Analysis

## Recommended new test filename
`tests/Feature/SystemPageAccessTest.php`

This name fits the existing class-per-file PHPUnit style in `tests/Feature`, stays focused on feature/system page coverage, and avoids overlapping with the existing behavior-heavy files:
- `PortalPagesTest.php` currently only checks bare success responses.
- `PortalExperienceTest.php` covers stateful workflows and CRUD.
- `AdminDashboardTest.php` covers dashboard metrics/view data.

A new `SystemPageAccessTest` should become the consolidated page-access matrix test for all GET-accessible user, admin, and public/auth pages.

---

## Existing test style and gaps

### Existing style
The repo uses classic PHPUnit `TestCase` classes, not Pest. New coverage should therefore use:
- `class ... extends TestCase`
- `use RefreshDatabase;`
- named `public function test_...(): void`

### What is already covered
- `PortalPagesTest.php`
  - confirms portal and admin page routes return successful responses
  - currently does **not** verify auth boundaries or redirects
- `AdminDashboardTest.php`
  - validates `admin.dashboard` content and view data for an admin user
- `PortalExperienceTest.php`
  - validates portal/admin actions and some page rendering during workflows

### Main gaps
1. Guest access rules are not asserted.
2. Non-admin authenticated user access to admin routes is not asserted.
3. Admin access to user routes is not asserted.
4. Public marketing/docs/auth pages are not covered.
5. Page-specific visible content is mostly not asserted.
6. Redirect behavior for guest-only auth pages when already authenticated is not asserted.

---

## Route coverage matrix

### Public pages
These should be accessible without authentication and should return `200`.

| Route name | URI | Audience | Expected |
|---|---|---|---|
| `docs` | `/docs` | public | 200 |
| `components` | `/components` | public | 200 |
| `laracasts` | `/laracasts` | public | 200 |
| `deploy` | `/deploy` | public | 200 |
| `changelog` | `/changelog` | public | 200 |

### Guest-only auth pages
These are in the `guest` middleware group.

| Route name | URI | Guest | Authenticated user/admin |
|---|---|---|---|
| `login` | `/login` | 200 | redirect away |
| `register` | `/register` | 200 | redirect away |
| `admin.login` | `/admin/login` | 200 | redirect away |
| `admin.register` | `/admin/register` | 200 | redirect away |

Because Laravel guest middleware redirects authenticated users to the app home by default, the practical expected result is a redirect, most likely to `route('portal.dashboard')`.

### Authenticated portal pages
These are inside `Route::middleware('auth')`.

| Route name | URI | Guest | Authenticated user | Admin |
|---|---|---|---|---|
| `portal.dashboard` | `/` | redirect to login | 200 | 200 |
| `portal.food-log` | `/food-log` | redirect to login | 200 | 200 |
| `portal.calendar` | `/calendar` | redirect to login | 200 | 200 |
| `portal.insights` | `/insights` | redirect to login | 200 | 200 |
| `portal.meal-plans` | `/meal-plans` | redirect to login | 200 | 200 |
| `portal.feedback` | `/feedback` | redirect to login | 200 | 200 |
| `portal.profile` | `/profile` | redirect to login | 200 | 200 |

### Admin-only pages
These are inside `Route::middleware(['auth', 'admin'])->prefix('admin')`.

| Route name | URI | Guest | Authenticated non-admin | Admin |
|---|---|---|---|---|
| `admin.dashboard` | `/admin` | redirect to login | forbidden or redirect by admin middleware | 200 |
| `admin.users` | `/admin/users` | redirect to login | forbidden or redirect by admin middleware | 200 |
| `admin.dietitians` | `/admin/dietitians` | redirect to login | forbidden or redirect by admin middleware | 200 |
| `admin.feedback` | `/admin/feedback` | redirect to login | forbidden or redirect by admin middleware | 200 |
| `admin.content` | `/admin/content` | redirect to login | forbidden or redirect by admin middleware | 200 |
| `admin.analytics` | `/admin/analytics` | redirect to login | forbidden or redirect by admin middleware | 200 |

Because the exact admin middleware response is not yet read from middleware code, the implementation should avoid overcommitting until verified. Preferred strategy:
- assert guest receives redirect to login
- assert non-admin does **not** get `200`
- if middleware behavior is confirmed, tighten to `403` or redirect target

---

## Recommended assertion strategy

### 1. Public pages
For each public route:
- `assertSuccessful()`
- `assertSeeText(...)` with a stable, page-specific heading if available

Suggested stable text assertions after view verification:
- `docs`: `Docs`
- `components`: `Components`
- `laracasts`: `Laracasts`
- `deploy`: `Deploy`
- `changelog`: `Changelog`

### 2. Guest auth pages
For each guest auth route:
- as guest: `assertSuccessful()`
- assert stable page text:
  - `login`: `Login`
  - `register`: `Register`
  - `admin.login`: `Admin Login`
  - `admin.register`: `Admin Register`

### 3. Authenticated users hitting guest-only auth pages
For a normal authenticated user and an admin user:
- `actingAs($user)->get(route(...))->assertRedirect(route('portal.dashboard'))`
- if the project behaves differently, at minimum assert `assertRedirect()`

### 4. Guest access to portal pages
For each portal route as guest:
- `assertRedirect(route('login'))`

### 5. Authenticated user access to portal pages
For each portal route with a normal user:
- `assertSuccessful()`
- `assertSeeText(...)` using stable hero heading:
  - dashboard: `Dashboard`
  - food-log: `Food Log`
  - calendar: `Food Prep Calendar`
  - insights: `Insights`
  - meal-plans: `Meal Plans`
  - feedback: `Dietitian Feedback`
  - profile: `Profile & Metrics`

### 6. Admin access to portal pages
Admin is still authenticated, and the portal nav explicitly exposes an admin shortcut from the user portal. So admin should also be able to load all portal pages:
- `assertSuccessful()`
- assert same portal page heading
- on at least one route, optionally assert `Admin` link text appears

### 7. Guest access to admin pages
For each admin route as guest:
- `assertRedirect(route('login'))`

### 8. Non-admin authenticated access to admin pages
For each admin route with a normal user:
- `assertStatus(403)` if admin middleware returns forbidden
- otherwise `assertRedirect(...)`
- safest initial implementation until middleware is confirmed:
  - `assertForbidden()` only after verifying middleware
  - otherwise `assertStatus(302)` + optional destination check
- fallback conservative assertion if parent wants resilience:
  - `$response->assertStatus(302)` or `$this->assertNotSame(200, $response->getStatusCode())`

### 9. Admin access to admin pages
For each admin route with an admin user:
- `assertSuccessful()`
- assert stable page-specific text:
  - dashboard: `Admin Dashboard`
  - users: `User Management Hub`
  - dietitians: `Dietitian Management`
  - feedback: `Feedback Management`
  - content: `Content Management`
  - analytics: `System Analytics`

---

## Concrete implementation plan

Create `tests/Feature/SystemPageAccessTest.php` with one focused class and route-provider arrays inside each test method, matching current repo style.

### Suggested class skeleton
- namespace `Tests\Feature`
- imports:
  - `App\Models\User`
  - `Illuminate\Foundation\Testing\RefreshDatabase`
  - `Tests\TestCase`

### Suggested test methods

#### 1. `test_public_pages_render_successfully(): void`
Purpose:
- cover `docs`, `components`, `laracasts`, `deploy`, `changelog`

Assertions:
- `assertSuccessful()`
- `assertSeeText()` with page heading

#### 2. `test_guest_auth_pages_render_successfully(): void`
Purpose:
- cover `login`, `register`, `admin.login`, `admin.register`

Assertions:
- `assertSuccessful()`
- `assertSeeText()` with form heading/title text

#### 3. `test_authenticated_users_are_redirected_away_from_guest_auth_pages(): void`
Purpose:
- verify guest middleware behavior for already-authenticated accounts

Setup:
- create normal user
- optionally also create admin and assert same behavior

Assertions:
- each guest-only route returns redirect, ideally to `route('portal.dashboard')`

#### 4. `test_guests_are_redirected_to_login_from_all_portal_pages(): void`
Purpose:
- cover all authenticated user portal GET pages for guest state

Assertions:
- `assertRedirect(route('login'))`

#### 5. `test_authenticated_users_can_view_all_portal_pages(): void`
Purpose:
- user-facing system page sweep for a normal user

Setup:
- `$user = User::factory()->create();`

Assertions per route:
- `assertSuccessful()`
- `assertSeeText()` heading

#### 6. `test_admins_can_view_all_portal_pages(): void`
Purpose:
- confirm admins still have access to the regular portal pages

Setup:
- `$admin = User::factory()->create(['is_admin' => true]);`

Assertions:
- `assertSuccessful()`
- `assertSeeText()` heading

#### 7. `test_guests_are_redirected_to_login_from_all_admin_pages(): void`
Purpose:
- cover guest boundary on admin pages

Assertions:
- `assertRedirect(route('login'))`

#### 8. `test_non_admin_users_cannot_access_admin_pages(): void`
Purpose:
- verify admin middleware blocks ordinary authenticated users

Setup:
- normal user

Assertions:
- ideally `assertForbidden()` for every admin route if middleware is 403
- if middleware redirects, assert redirect target instead
- parent agent should first inspect `app/Http/Middleware` or run tests once before finalizing exact assertion

#### 9. `test_admins_can_view_all_admin_pages(): void`
Purpose:
- full admin page rendering sweep

Setup:
- admin user

Assertions:
- `assertSuccessful()`
- `assertSeeText()` heading

---

## Expected route arrays for implementation

### Public page map
```php
[
    'docs' => 'Docs',
    'components' => 'Components',
    'laracasts' => 'Laracasts',
    'deploy' => 'Deploy',
    'changelog' => 'Changelog',
]
```

### Guest auth page map
```php
[
    'login' => 'Login',
    'register' => 'Register',
    'admin.login' => 'Admin Login',
    'admin.register' => 'Admin Register',
]
```

### Portal page map
```php
[
    'portal.dashboard' => 'Dashboard',
    'portal.food-log' => 'Food Log',
    'portal.calendar' => 'Food Prep Calendar',
    'portal.insights' => 'Insights',
    'portal.meal-plans' => 'Meal Plans',
    'portal.feedback' => 'Dietitian Feedback',
    'portal.profile' => 'Profile & Metrics',
]
```

### Admin page map
```php
[
    'admin.dashboard' => 'Admin Dashboard',
    'admin.users' => 'User Management Hub',
    'admin.dietitians' => 'Dietitian Management',
    'admin.feedback' => 'Feedback Management',
    'admin.content' => 'Content Management',
    'admin.analytics' => 'System Analytics',
]
```

---

## Notes for the parent agent before implementation

1. **Check admin middleware behavior**
   - Inspect the `admin` middleware before locking in `assertForbidden()` vs redirect assertions for non-admin users.

2. **Check auth page headings**
   - If login/register Blade pages use different visible text than assumed here, adjust the `assertSeeText()` strings accordingly.

3. **Avoid duplicating behavioral CRUD coverage**
   - This file should stay focused on system/page access and visible rendering, not model mutation workflows already covered elsewhere.

4. **Possible cleanup**
   - Once `SystemPageAccessTest.php` exists, `PortalPagesTest.php` may become redundant or can remain as a lightweight smoke test. That is a parent-level decision.

---

## Summary recommendation

Best new file:
- `tests/Feature/SystemPageAccessTest.php`

Best scope:
- full GET route access matrix for public pages, guest auth pages, authenticated portal pages, and admin pages

Best assertion mix:
- guests: redirect to login on protected routes
- authenticated users: success on portal routes, redirect away from guest auth routes
- non-admin users: blocked from admin routes
- admins: success on both portal and admin routes
- page content: assert stable visible headings for each route