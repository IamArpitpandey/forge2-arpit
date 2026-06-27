# PulseDesk — Multi-Tenant Support Desk SaaS

Built for Forge 2 · Edition 1 (NMG Labs) — a multi-agent AI development sprint using Hermes (orchestrator) + OpenClaw (coder) over a shared local workspace, with both agents running on EastRouter (`z-ai/glm-5.1`).

## What It Is

PulseDesk is a hosted help-desk / ticketing product for multiple companies (tenants) to manage customer support — a focused mini-Zendesk. Built solo, in one day, by directing two AI agents rather than hand-writing the features.

## Stack

- **Backend:** Laravel **13.17.0** + MySQL 8 + Sanctum
  - *Note: the spec called for Laravel 11. The `composer create-project` scaffold pulled Laravel 13 (latest) by default. Pinning to Laravel 11 was attempted but blocked — all Laravel 11.x releases are blocked by Composer security advisories (CVEs only patched in 12.x/13.x). Rather than force an insecure install, we kept Laravel 13, which is fully backwards-compatible with the Laravel 11 feature set used here.*
- **Frontend:** React 19 + Vite + Tailwind CSS v4
- **Auth:** Laravel Sanctum (token-based)
- **Models:** EastRouter — `z-ai/glm-5.1` (both Hermes and OpenClaw)

## Run Steps (Fresh Clone)

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Ensure MySQL is running, then create the database:
mysql -u root -e "CREATE DATABASE pulsedesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan serve
```
Backend runs at `http://127.0.0.1:8000`.

### Frontend
```bash
cd frontend
npm install
npm run dev
```
Frontend runs at `http://127.0.0.1:5173`.

Deployed URL:  https://frontend-ten-tan-26.vercel.app

password: password
email: admin@acme.test

## Demo Logins

All seeded users use the password `password`.

| Role | Email |
|---|---|
| Admin | admin@acme.test |
| Agent | agent1@acme.test |
| Agent | agent2@acme.test |
| Customer | customer1@acme.test |
| Customer | customer2@acme.test |

Seeded data: 1 organization (Acme), 5 users, ~12 tickets across all statuses/priorities, 5-6 tags, 2-3 replies per ticket (mix of public/internal).

## Multi-Tenancy

Every tenant-scoped model uses a `BelongsToTenant` trait + `BelongsToTenantScope` global scope, which:
- Auto-injects `WHERE organization_id = <current tenant>` on every query.
- Auto-fills `organization_id` on create from the authenticated user — **never** from client input.
- Tenant isolation is covered by automated feature tests (see `backend/tests/Feature/TicketApiTest.php`) — an Org A user gets a 404 attempting to fetch an Org B ticket, and list endpoints only ever return the caller's own org data.

## API Overview

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/register` | Register |
| POST | `/api/login` | Login (returns Sanctum token) |
| POST | `/api/logout` | Logout |
| GET | `/api/me` | Current user |
| GET | `/api/tickets` | List (filters: `status`, `priority`, `assignee_id`, `q`) |
| POST | `/api/tickets` | Create |
| GET | `/api/tickets/{id}` | Show |
| PUT | `/api/tickets/{id}` | Update |
| DELETE | `/api/tickets/{id}` | Delete (staff only) |
| GET | `/api/tickets/{id}/replies` | List replies (internal hidden from customers) |
| POST | `/api/tickets/{id}/replies` | Add reply |
| GET | `/api/users` | Org members (for assignee dropdown) |
| GET | `/api/tags` | Org tags |

## Agent Workflow

This project was built by directing two AI agents rather than writing features by hand:

- **Hermes** (orchestrator/PO) — decomposed the spec into a sprint backlog, planned the data model and multi-tenancy strategy, and implemented the auth layer, database seeder, and frontend.
- **OpenClaw** (coder) — implemented the Ticket CRUD API, policies, replies, filters/search, and feature tests.

Both agents ran on EastRouter (`z-ai/glm-5.1`), committed to git independently, and all merges to `main` were performed by the human (me).

**Note on Slack:** The required Slack-based agent-coordination loop (handbook §05) could not be completed — OpenClaw's Slack pairing repeatedly failed/expired during setup despite multiple retries (see `agent-log.md` for the full troubleshooting trail). Both agents were run directly via their CLIs instead, with all prompts/responses logged manually in `agent-log.md` to preserve the orchestration audit trail.

## Testing

```bash
cd backend
php artisan test
```
13 feature tests covering tenant isolation, RBAC, and ticket CRUD happy paths — all passing.

## What's Done vs What Slipped

**Done (Must-tier):** Multi-tenancy, auth + roles, ticket CRUD, threaded replies (public/internal), filters/search, REST API + React frontend, seeded demo data.

**Not attempted (Should/Stretch-tier):** SLA policies, activity log, dashboard metrics, notifications, canned responses, real-time updates.