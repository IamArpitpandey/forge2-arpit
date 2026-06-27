# Sprint 01 — PulseDesk MVP Planning

## Goal
Build a working multi-tenant ticket system with auth, CRUD, replies, filtering, and seeded demo data. Frontend can log in and manage tickets end-to-end.

## Stack (locked by scaffold)
- Backend: Laravel 11 + PHP 8.2 (CI) + MySQL 8 + Sanctum
- Frontend: React 19 + Vite + Tailwind
- CI: already wired (`.github/workflows/ci.yml` — MySQL 8 service, `php artisan migrate` + test)

## Entities / Data Model

**Must-tier:**
1. **Organization** (tenant) — id, name, slug, timestamps. Tenancy root; every business record hangs off `organization_id`.
2. **User** — id, organization_id, name, email, password, role (enum: admin|agent|customer), timestamps. Sanctum issues personal-access tokens.
3. **Ticket** — id, organization_id, subject, description, status (open|pending|resolved|closed), priority (low|medium|high|urgent), requester_id, assignee_id, timestamps.
4. **TicketReply** — id, ticket_id, author_id, body, type (public|internal), timestamps. `public` visible to customer, `internal` agents/admins only.
5. **Tag** — id, organization_id, name (unique per org), timestamps. Many-to-many with Ticket via `ticket_tag` pivot.

**Should-tier (stretch, time permitting):** SlaPolicy, ActivityLog.

Migration order: `organizations → users → tickets → ticket_replies`, `organizations → tags → ticket_tag`.

## Multi-Tenancy Strategy
Org is always derived from the authenticated session (Sanctum token), never from a client-supplied `organization_id`.
- `BelongsToTenant` global scope trait on every tenant-scoped model — auto-injects `WHERE organization_id = auth_user.org_id`.
- Controllers never accept `organization_id` from the request body.
- Policy layer for role checks on top of tenancy scoping.

## RBAC Matrix
| Role | List | Create | Update status | Assign | Delete | Internal notes |
|---|---|---|---|---|---|---|
| admin | Y | Y | Y | Y | Y | Y |
| agent | Y | Y | Y | Y | - | Y |
| customer | Y (own) | Y | Y (own, close) | - | - | - (public only) |

## API Surface
```
POST   /api/register
POST   /api/login
POST   /api/logout
GET    /api/me

GET    /api/tickets               (filterable: status, priority, assignee_id, q, tag)
POST   /api/tickets
GET    /api/tickets/{ticket}
PUT    /api/tickets/{ticket}
DELETE /api/tickets/{ticket}

GET    /api/tickets/{ticket}/replies
POST   /api/tickets/{ticket}/replies

GET    /api/users
GET    /api/tags
```

## Sprint 1 Tasks
1. Tenancy foundation — Organization + User models/migrations, `BelongsToTenant` trait, Sanctum install + token issuance.
2. Auth endpoints — register/login/logout/me + demo seeder skeleton (1 org, 1 admin, 2 agents, 2 customers).
3. Tickets CRUD — model, migration, factory, resource controller, form requests, `TicketPolicy`, Tag model + pivot.
4. Ticket replies — model, migration, nested resource controller, public/internal visibility enforcement.
5. List with filters + search — status/priority/assignee/tag filters, text search on subject+description, pagination, API Resources.
6. Database seeder — 1 org (Acme), 5 users, ~12 tickets, 5-6 tags, 2-3 replies per ticket.
7. Feature tests — tenant isolation (Org A can't fetch Org B's ticket → 404), role tests, CRUD happy path, filter/search assertions.
8. Frontend MVP — auth context, login page, ticket list + filter bar, ticket detail with reply thread (public/internal styling), create ticket form, role-aware UI.

## Sprint 1 Exit Criteria
A judge can run README steps from a fresh clone, get seeded data, log in as all 3 roles, create/view/reply/filter tickets, and confirm Org A truly cannot see Org B's data.

## Environment Notes (resolved before Sprint 1 execution)
- Local PHP was 8.5.4 with only `pdo_sqlite` loaded — `pdo_mysql` was missing. **Fixed**: uncommented `extension=pdo_mysql` in `php.ini`.
- MySQL/MariaDB was already running via XAMPP on port 3306 (root, no password) — verified with `SHOW DATABASES;`.
- CI pins PHP 8.2 — kept implementation syntax at 8.2-compatible level even though local PHP is 8.5.4.

## Outcome
(Fill in at end of sprint: what shipped, what slipped.)