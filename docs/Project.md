# PROJECT.md — ISU Portal (Context for Claude)

> Read this file at the start of every session. It holds the rules, conventions, and domain logic.
> Build order and task status are in `plan.md`. If the two conflict, ask the developer.

## 1. What this project is

A single-campus **College School Portal** with enrollment, grading, and **cash-based accounting**.
Used by 5 roles: **Admin, Registrar, Accounting, Faculty, Student**.

This is a production system that handles student records and money. Prefer correctness, auditability, and security over speed or cleverness.

## 2. Stack

- PHP 8.2+ (Laravel 12, or 13 if PHP 8.3+), Composer
- Laravel Breeze with **React + Inertia** (TypeScript if enabled)
- MySQL
- `spatie/laravel-permission` (RBAC)
- `spatie/laravel-activitylog` (audit)
- Pest (tests), Pint (PHP style), ESLint/Prettier (JS/TS)
- Windows dev machine (PowerShell). Keep commands compatible, or give both variants.

## 3. Common commands

```bash
composer install && npm install
php artisan migrate --seed
php artisan serve
npm run dev
npm run build

php artisan test            # or: ./vendor/bin/pest
./vendor/bin/pint           # format PHP
php artisan migrate:fresh --seed   # DEV ONLY. Never in production
```

## 4. Authentication

- Login uses **`user_id`** (string, unique, e.g. `2025-00123`) and password. **Not email.**
- `email` is nullable and not used for login.
- **No public registration.** Only the Registrar (and Admin for staff) create accounts.
- `must_change_password` forces a password change on first login.
- Keep Breeze throttling and lockout, keyed on `user_id`.
- When changing Breeze files (`LoginRequest`, `AuthenticatedSessionController`, login React page), update the throttle key, validation rules, and error messages together.

## 5. Roles and authorization

| Role | Scope |
|---|---|
| Admin | System settings, role management, audit logs, staff accounts |
| Registrar | Accounts, academic setup, enrollment, subject/unit assignment per term |
| Accounting | Fees, assessments, cash payments, receipts, voids, scholarships |
| Faculty | Own sections only: class lists, grade entry |
| Student | Own records only, read-only |

**Rules:**
- Enforce authorization in the **backend** with Policies, Gates, and middleware. Never rely on hidden UI.
- Every controller action that reads or changes data must be authorized.
- Scope queries by ownership (e.g. faculty sees only their sections, student only their own rows). Never trust an ID from the request without checking ownership.
- Use `can:` permissions, not hard-coded role names, wherever practical.

### Identity model

- **One `users` table** for every login. It holds auth data only (no names).
- **`students`** is the master list. `students.user_id` is nullable: a student record can exist before an account is created. Required `program_id`.
- **`employees`** covers faculty, registrar, accounting, and admin. The role lives in spatie (`model_has_roles`), never in a column.
- Department assignment is the `department_employee` pivot. A student's department comes from `program -> department` (single source of truth).
- ID formats: students `YYYY-NNNNN`, employees `EMP-NNNN`. `users.user_id` equals the student_id/employee_id.

### Department scoping (registrar)

- A registrar can only view, update, create accounts for, and enroll students whose program belongs to one of their assigned departments.
- **Always** list students with `Student::visibleTo($request->user())`. Never `Student::all()` or an unscoped query in a registrar-facing screen.
- Always authorize single records with `StudentPolicy` (`view`, `update`, `createAccount`, `enroll`). Do not re-implement the department check in controllers.
- Registrars may create only `student` and `faculty` accounts (`UserPolicy::assignRole`), and only faculty inside their own departments. Admin creates registrar/accounting/admin accounts.
- Account creation goes through the Actions in `app/Actions/Accounts`. They authorize internally, generate a random temporary password, set `must_change_password`, and audit-log. The temporary password is shown once and never stored in plain text.
- The `hashed` cast on `User::password` hashes automatically. Pass the plain password to `User::create()`; do not call `Hash::make()` first.

## 6. Grading rules (critical)

Allowed values: `1.0, 1.25, 1.5, 1.75, 2.0, 2.25, 2.5, 2.75, 3.0, 5.0, INC, DROPPED`
(Defined in a single `GradeValue` enum. Do not scatter literals.)

- **`5.0` is final and immutable.** No role may edit or delete a `5.0` grade, including Admin. Enforce in the Policy, in the model (`updating`/`deleting` events), and with tests.
- A retake is a **new** enrollment item and a **new** grade row. Never overwrite the failed row.
- `INC` can only change into a numeric grade. Resolution is audit-logged. Expiry behavior is configurable (see `plan.md` open questions).
- `DROPPED` carries no credit and blocks further grading for that item.
- Students see only **finalized** grades.
- Faculty can grade **only their own sections**.

## 7. Accounting rules (critical)

- Payments are **cash only** in v1. No online payment code.
- The **ledger is append-only**. Never update or delete `ledger_entries`. Fix mistakes with a **reversal entry**.
- Money type: `decimal(12,2)` in the DB, string or integer-centavo handling in PHP. **Never float.**
- A student's balance is **computed from the ledger**. Do not keep a manually updated balance column as a source of truth.
- Wrap every money operation in `DB::transaction()`.
- Official Receipt (OR) numbers are sequential, unique, and never reused. Generate them safely (lock/sequence table) to avoid duplicates under concurrency.
- Void/refund requires a specific permission and a mandatory reason, and is audit-logged.

## 8. Enrollment rules

Status flow: `Draft -> For Assessment -> Assessed -> Partially Paid / Paid -> Enrolled -> Locked`

Validate before assessment: prerequisites passed, unit limit, section capacity, no schedule conflict. A subject already passed cannot be re-enrolled. A subject with `5.0` can be retaken. Locked enrollments need a special permission to change.

## 9. Code conventions

**Backend**
- Thin controllers. Put business logic in **Action classes or Services** (e.g. `AssessEnrollment`, `RecordCashPayment`, `SubmitGrades`).
- Use **Form Requests** for validation.
- Use **Eloquent API Resources** (or explicit arrays) when sending data to Inertia. Never pass whole models containing sensitive fields to the frontend.
- Use **enums** for statuses, grade values, and roles.
- Eager-load relationships to avoid N+1 queries.
- Use **soft deletes** on students, subjects, and sections. No hard deletes on records that have history.
- Use migrations for every schema change. Never edit an already-run migration in a shared/production DB. Add a new one.
- Add foreign keys, unique constraints, and indexes in migrations.

**Frontend (React + Inertia)**
- Pages in `resources/js/Pages/<Area>/...`, shared components in `resources/js/Components`.
- Use Inertia `useForm` for forms and show server validation errors.
- Frontend checks are for UX only. The backend is the authority.
- Keep components small and typed (if TypeScript is on).

**Naming**
- DB: `snake_case`, plural tables. Models: singular `StudlyCase`.
- Routes grouped by role/area with middleware, e.g. `/registrar/...`, `/accounting/...`, `/faculty/...`, `/student/...`.

## 10. Testing

Write Pest tests with every feature. Priorities:
1. **Authorization**: each role can reach only its own routes; student A cannot see student B; faculty cannot grade another faculty's section.
2. **Grades**: `5.0` cannot be modified; INC transitions; DROPPED behavior.
3. **Ledger**: totals, reversal on void, decimal precision, OR sequence.
4. **Enrollment**: prerequisites, unit limit, capacity, schedule conflicts.

Run the test suite before saying a task is done.

## 11. Security and privacy

- Never commit `.env`, secrets, or real student data.
- Use seeders/factories with fake data only.
- Passwords are hashed (Laravel default). Never log or display passwords. Default/initial passwords must force a change.
- Audit-log: logins of staff, account creation, enrollment changes, assessments, payments, voids, grade changes, INC resolutions.
- Follow the Data Privacy Act: least-privilege access, no unnecessary personal data in logs or error messages.
- Production: `APP_DEBUG=false`, HTTPS, secure cookies.

## 12. How Claude should work in this repo

1. **Read `plan.md`** to find the current phase. Work on one phase/task at a time.
2. **Before coding a non-trivial feature, briefly state the approach** (files to change, migrations, policies, tests). Wait for confirmation if there are open questions.
3. **Ask, don't guess**, when a business rule is unclear. Check the open questions list in `plan.md`.
4. Make **small, reviewable changes**. Do not refactor unrelated code.
5. Always include: migration (if needed), model, policy, form request, controller/action, route, React page, and tests.
6. **Do not** weaken authorization, skip validation, or bypass the ledger/grade rules to make something "work".
7. **Do not** run destructive commands (`migrate:fresh`, dropping tables, deleting data) unless explicitly told it is a dev database.
8. After finishing, update the checkboxes and progress log in `plan.md`, and list any new open questions.
9. Reply in **Taglish** if the developer writes in Tagalog/Taglish. Keep code, comments, and identifiers in English.

## 13. Glossary

- **Term**: a semester (1st Sem, 2nd Sem, Summer) within an academic year
- **Section**: a scheduled offering of a subject in a term, with faculty, room, capacity
- **Assessment**: computed fees for an enrollment
- **OR**: Official Receipt
- **INC**: Incomplete grade
- **SOA**: Statement of Account