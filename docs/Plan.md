# PLAN.md — College School Portal (ISU Portal)

> Living document. Update the checkboxes at the end of every working session.
> Rules and conventions live in `project.md`. This file is about **what to build and in what order**.

## 1. Scope

A single-campus college portal with enrollment, grading, and cash-based accounting.

| Decision | Value |
|---|---|
| Campus | Single campus only (no multi-tenant) |
| Payments | **Cash only** (cashier encodes at Accounting office). No online payment in v1 |
| Grading | `1.0` to `3.0` (passing), `5.0` (failed), `INC`, `DROPPED` |
| Login | `user_id` + password (no public registration) |
| Stack | Laravel 12 (or 13), Breeze (React + Inertia), MySQL |

### Out of scope for v1
Online payments (GCash/bank), multi-campus, parent portal, mobile app, LMS features.

## 2. Roles

| Role | Can do |
|---|---|
| **Admin** | System settings, manage roles/permissions, view audit logs, manage staff accounts |
| **Registrar** | Create student/faculty accounts, academic setup (programs, subjects, curricula, terms, sections), enrollment, assign subjects and units per semester |
| **Accounting** | Fee schedules, assessments, encode cash payments, issue receipts, void with reason, scholarships/discounts, reports |
| **Faculty** | View own sections and class lists, encode and submit grades for own sections only |
| **Student** | View own enrollment, schedule, grades, and statement of account (read-only) |

Authorization is enforced in the **backend** (Policies/Gates via `spatie/laravel-permission`). Hiding buttons in React is UX only.

## 3. Grading rules

Allowed values: `1.0, 1.25, 1.5, 1.75, 2.0, 2.25, 2.5, 2.75, 3.0, 5.0, INC, DROPPED`
(Confirm the exact scale with the school. Adjust in one place: a `GradeValue` enum.)

| Grade | Meaning | Editable after submit? |
|---|---|---|
| 1.0 – 3.0 | Passed | Locked once submitted/finalized |
| **5.0** | **Failed. Final.** | **Never editable. Not by faculty, registrar, or admin.** |
| `INC` | Incomplete | Yes, but only to be resolved into a real grade (see below) |
| `DROPPED` | Officially dropped | Locked. No credit, no re-grade |

**5.0 is immutable.** Enforce it at three levels:
1. **Policy**: `update` is denied if the current value is `5.0`.
2. **Model**: `updating` event throws if the original value is `5.0`.
3. **Database**: a trigger (or at minimum a test) that blocks updates on `5.0` rows.

A student who fails and retakes the subject gets a **new enrollment item and a new grade row** in a later term. The old `5.0` row stays forever.

**INC rules** (make configurable, confirm with the school):
- INC can only be changed into a numeric grade (`1.0–3.0` or `5.0`).
- INC has a resolution deadline (e.g. 1 year). If unresolved, the system or registrar converts it to `5.0`.
- Every INC resolution is logged in the audit trail.

**Open question:** If a faculty encodes a wrong grade by mistake *before* it is `5.0`, there is a correction flow (request, approval by registrar, logged). If the mistaken grade *is* `5.0`, the rule says no changes. Decide whether the school wants a documented exception process outside the system.

## 4. Data model (summary)

**Auth and profiles** (decided)
- `users`: auth lang. `user_id` (unique), `password`, `email` (nullable), `status`, `must_change_password`. Walang pangalan dito.
- `students`: **master list**. May personal info (student_id, first/middle/last name, suffix, sex, contact_number) at `program_id` (required). `user_id` ay **nullable**: umiiral ang student kahit wala pang account.
- `employees`: faculty, registrar, accounting, admin sa iisang table. Role ay nasa `spatie/laravel-permission`, hindi sa column.
- `department_employee` (pivot): ang mga department na naka-assign sa employee. Ito ang scope ng registrar.

**ID formats** (decided): students `YYYY-NNNNN` (hal. `2025-00123`), employees `EMP-NNNN` (hal. `EMP-0001`). Ang `users.user_id` ay kapareho ng student_id/employee_id.

**Department scoping** (decided): `departments` -> `programs` -> `students`. Ang registrar ay may access lang sa students ng mga naka-assign niyang department (view, update, create account, enroll). Isang query scope, `Student::visibleTo($user)`, at `StudentPolicy` ang bantay. Admin at Accounting ay walang dept restriction.

**Academic**
- `departments`, `programs` (belongs to department), `subjects` (code, title, lecture_units, lab_units)
- `curricula`, `curriculum_subjects` (year level, term, prerequisites)
- `academic_years`, `terms`
- `sections` (subject, term, faculty, schedule, room, capacity)

**Enrollment and grades**
- `enrollments` (student, term, status)
- `enrollment_items` (enrollment, section)
- `grades` (enrollment_item, value, status, submitted_at, finalized_at)

**Accounting**
- `fee_schedules`, `assessments`
- `ledger_entries` (append-only)
- `payments`, `receipts` (sequential OR number)
- `discounts` / `scholarships`

**Audit**
- `activity_log` (via `spatie/laravel-activitylog`)

## 5. Accounting rules (non-negotiable)

1. Ledger is **append-only**. No edit, no delete. Corrections are reversal entries.
2. Money is `decimal(12,2)` (or integer centavos). Never float.
3. Balance is **computed from the ledger**, not stored and manually updated.
4. All money operations run inside `DB::transaction()`.
5. Official Receipt numbers are sequential, unique, and never reused.
6. Void/refund needs its own permission and a mandatory reason.
7. Every payment, void, and fee change is audit-logged.

## 6. Enrollment workflow

```
Draft -> For Assessment -> Assessed -> Partially Paid / Paid -> Enrolled -> Locked
```

Validations before assessment: prerequisites met, unit limit, section capacity, no schedule conflict, no unresolved holds (e.g. unpaid previous balance if the school requires it).

Subjects already passed cannot be enrolled again. Subjects with a `5.0` can be retaken.

## 7. Phases

### Phase 0 — Setup
- [x] Laravel installed in project folder (see PHP version note below)
- [x] Breeze React + Inertia installed
- [x] MySQL configured, `.env` set, `.env.example` updated
- [x] Git initialized, first commit
- [x] PHPUnit + Pint configured (using PHPUnit instead of Pest per project.md)
- [x] `project.md` and `plan.md` added to repo root

> Dev note: PHP 8.2 gets Laravel 12. PHP 8.3+ gets Laravel 13. Either is fine.

### Phase 1 — Auth and RBAC
- [x] `users` migration with `user_id`, nullable `email`, `must_change_password`
- [x] Login uses `user_id` (`LoginRequest`, login page, throttle key)
- [x] Remove public register and email verification routes
- [x] Install `spatie/laravel-permission`; seed the 5 roles and permissions
- [x] Role-based redirect after login
- [x] Force password change on first login
- [x] Schema: users, departments, programs, students, employees, department_employee (pivot)    
- [ ] Registrar: create student accounts (single and bulk) from the master list, department-scoped
- [ ] Registrar: create faculty accounts only inside assigned departments
- [ ] Admin: create registrar/accounting/admin accounts
- [ ] Registrar: edit/deactivate accounts, reset password (in scope only)
- [ ] Bulk import students from CSV/Excel
- [x] Tests: each role can only reach its own routes (RbacTest - 10 tests passing)

### Phase 2 — Academic setup (Registrar)
- [ ] Programs, subjects, curricula, prerequisites
- [ ] Academic years and terms (one active term at a time)
- [ ] Sections: schedule, room, faculty, capacity
- [ ] Tests: prerequisite and schedule conflict logic

### Phase 3 — Enrollment (Registrar)
- [ ] Create enrollment for a student per term
- [ ] Add/remove subjects with all validations
- [ ] Status transitions through the workflow
- [ ] Lock enrollment after the enrollment period
- [ ] Tests: unit limit, capacity, conflicts, prerequisites

### Phase 4 — Accounting (cash)
- [ ] Fee schedules per program/term
- [ ] Assessment computed from units + fees, creates ledger charges
- [ ] Encode cash payment, issue OR, update ledger
- [ ] Void with reason (reversal entry)
- [ ] Scholarships/discounts
- [ ] Statement of account (per student)
- [ ] Enrollment status updates when the required payment is reached
- [ ] Tests: ledger totals, void reversal, OR sequence, decimal precision

### Phase 5 — Faculty and grades
- [ ] Faculty sees only own sections and class lists
- [ ] Grade entry with the allowed values only
- [ ] Submit/finalize flow
- [ ] `5.0` immutability (policy + model + DB)
- [ ] INC resolution and deadline handling
- [ ] DROPPED handling (no credit, blocks further grading)
- [ ] Tests: cannot edit `5.0`, cannot grade another faculty's section

### Phase 6 — Student portal
- [ ] Dashboard: current enrollment, schedule
- [ ] Grades per term (only finalized grades are visible)
- [ ] Statement of account
- [ ] Tests: student A can never see student B's data

### Phase 7 — Reports
- [ ] Enrollment lists per section/program
- [ ] Daily collection report
- [ ] Receivables report
- [ ] Grade sheets and transcript view
- [ ] Export to Excel/PDF

### Phase 8 — Hardening and deploy
- [ ] Review audit logs cover all sensitive actions
- [ ] Automated DB backups and a tested restore
- [ ] `APP_DEBUG=false`, HTTPS, secure cookies, rate limiting
- [ ] Production build (`npm run build`), queue worker, scheduler
- [ ] Data Privacy Act review (access control, retention)
- [ ] User acceptance testing with Registrar, Accounting, Faculty

## 8. Open questions

- [ ] Exact grading scale (are 1.25-style steps used?) and passing grade
- [ ] INC deadline length and what happens when it expires
- [ ] Is there a minimum payment required before status becomes Enrolled?
- [ ] Unit limit per term (and overload approval process)
- [x] Format of `user_id`: decided. Students `YYYY-NNNNN`, employees `EMP-NNNN`
- [ ] Should Accounting be limited per department too, or see all students? (currently all)
- [ ] Can a registrar be assigned to more than one department? (currently yes, via pivot)
- [ ] Does Accounting need installment/payment plans in v1?
- [ ] Policy for grade correction requests before finalization

## 9. Progress log

| Date | Done | Next |
|---|---|---|
| 2026-11-21 | Phase 0 Setup complete; Phase 1 foundation (schema, models, roles/permissions, seeders, factories, RbacTest) complete. All 10 new RBAC tests pass (26 assertions). `migrate:fresh --seed` runs cleanly. | Phase 1 Step 2: Login/auth pages, remove registration/email verification, role-based redirect, force password change |
| 2026-11-22 | Phase 1 Step 2 (AUTH ONLY) complete: login by user_id with throttling (5 attempts), authentication with status='active', session regeneration, last_login_at, activitylog for staff, removed registration/email verification/password reset routes/controllers/pages/tests, removed verified middleware, EnsurePasswordIsChanged middleware, force change password page with validation, role-based redirect (admin>registrar>accounting>faculty>student), role-protected placeholder pages, Inertia shared props, profile page (read-only user_id/full_name/roles, password update only), confirm-password kept, Password::defaults() in AppServiceProvider, EnsureUserIsActive middleware. All 59 tests pass (152 assertions). | Phase 1 Step 3: Registrar account management (create student/faculty accounts, department-scoped) |