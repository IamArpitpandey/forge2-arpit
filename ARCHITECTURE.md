# Architecture -- PulseDesk

## Multi-tenancy approach
Every record is scoped to `organization_id`. Tenant is derived from the authenticated
user's session (via Laravel Sanctum token) -- NOT from any client-supplied header or param.
A global Eloquent scope (`TenantScope`) is applied on Ticket, Comment, SlaPolicy, and
ActivityLog models. Middleware verifies the token and binds `auth()->user()->organization_id`
to all queries automatically.

## Data model
- Organization (tenant) -- id, name, slug, timestamps
- User -- id, organization_id, name, email, password, role (admin|agent|customer), timestamps
- Ticket -- id, organization_id, subject, description, status, priority, requester_id, assignee_id, timestamps
- Comment -- id, ticket_id, author_id, body, is_internal, timestamps
- SlaPolicy -- id, organization_id, priority, response_minutes, resolution_minutes
- ActivityLog -- id, ticket_id, actor_id, action, meta (json), created_at

## API routes (routes/api.php)
| Method | Path | Auth | Notes |
| --- | --- | --- | --- |
| POST | /api/register | - | Creates user + org |
| POST | /api/login | - | Returns Sanctum token |
| GET | /api/tickets | agent/admin | Tenant-scoped, filterable by status/priority |
| POST | /api/tickets | any auth | Creates ticket under auth user org |
| GET | /api/tickets/{id} | tenant | Tenant-scoped fetch |
| PUT | /api/tickets/{id} | agent/admin | Status/priority/assignee update |
| POST | /api/tickets/{id}/comments | tenant | Public reply or internal note |

## Key decisions
- Laravel 13 used instead of 11 -- PHP 8.5.4 triggered advisory block on Laravel 11.
  Laravel 13 resolved cleanly. Documented in README.md.
- Sanctum chosen over Passport -- simpler token auth, sufficient for SPA + API use case.
- TenantScope as global Eloquent scope -- ensures no accidental cross-tenant data leak.
- EastRouter used for all AI model calls (z-ai/glm-5.1) via Hermes and OpenClaw agents.
- CI PHP version bumped 8.3 -> 8.4 to match local composer.lock resolution.
- Slack OAuth failed -- all agent interaction logged honestly in agent-log.md instead.