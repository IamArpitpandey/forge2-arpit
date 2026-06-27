# Sprint 02 — CI Stabilisation, Test Expansion & Submission Polish

**Duration:** Session 2 (same day, continued from Sprint 01)
**Human operator:** Arpit Pandey (@IamArpitpandey)
**Agents active:** Hermes (backend / DevOps), OpenClaw (frontend / test runner)
**Commits this sprint:** 5a76b1e, 8cac316, docs commits

---

## Goal

1. Fix failing GitHub Actions CI pipeline (backend job).
2. Expand automated test coverage beyond the Sprint 01 baseline.
3. Finalise documentation: README, ARCHITECTURE.md, SUBMISSION.md, evidence screenshots.

---

## What Happened (Honest Account)

### 2.1 — Composer Lock Mismatch (Root Cause)

CI backend job was failing with:

```
Your lock file does not contain a compatible set of packages.
symfony/clock is locked to version v8.1.0 and requires php >=8.4.1
your php version (8.3.31) does not satisfy that requirement
```

**Root cause:** Local machine runs PHP 8.5.4. `composer update` ran locally and regenerated `composer.lock` with packages that require PHP ≥ 8.4.1. CI was pinned to PHP 8.3. Version skew between dev machine and CI environment caused the mismatch.

**Hermes prompt (human → Hermes):**
> Root cause: local PHP is 8.5, CI pins PHP 8.3, composer.lock now needs PHP 8.4.1+. Fix: update .github/workflows/ci.yml to use PHP 8.4 instead. Commit and push.

**Hermes action:**
- Edited `.github/workflows/ci.yml`: bumped `php-version: '8.3'` → `'8.4'`
- Committed: `fix(ci): bump PHP version 8.3 -> 8.4` (5a76b1e)
- Pushed to `origin/main`

**Result:** CI backend job turned green. ✅

---

### 2.2 — Test Suite Expansion

**OpenClaw prompt (human → OpenClaw):**
> Run the full backend test suite once more to confirm everything still passes after recent changes.

**Results after PHP 8.4 fix:**
- Tests: **15 passed** (up from 13 in Sprint 01)
- Assertions: **38**
- Failures: **0**
- Time: < 5 seconds

New tests added during Sprint 02 (by Hermes):
- `TenantIsolationTest` — verifies Org A tickets are not visible to Org B authenticated users
- `TicketStatusTransitionTest` — verifies valid state machine transitions (open → in_progress → resolved)

---

### 2.3 — Evidence Screenshots

All screenshots collected and committed to `evidence/screenshots/`:

| File | Contents |
|---|---|
| `01-login.png` | Login page, both tenant credentials |
| `02-ticket-list.png` | Ticket list for acme tenant |
| `03-ticket-detail.png` | Single ticket detail + comment thread |
| `04-agents-running.png` | Hermes + OpenClaw terminals active |
| `05-ci-green.png` | GitHub Actions — green run (commit 5a76b1e) |

---

### 2.4 — Slack Integration (Transparent Note)

The handbook specifies Slack as the channel between human and agents. During this project, Slack workspace creation and bot token provisioning hit repeated OAuth permission errors in the available environment.

**Decision (human):** Document the failure honestly rather than fabricate a Slack log. All human↔agent interaction occurred via the Claude-native tool terminal interface, which is functionally equivalent (human sends prompt → agent executes → human reviews output). This is recorded in `agent-log.md`.

---

### 2.5 — README & Documentation Finalised

- `README.md` — full run steps, demo credentials, stack notes, Laravel 13 rationale documented
- `ARCHITECTURE.md` — system diagram, tenant isolation explanation, EastRouter model routing
- `SUBMISSION.md` — checklist filled with honest tick/cross per actual state
- `agent-log.md` — full real interaction log, no fabrication

---

## Sprint 02 Summary

| Item | Status |
|---|---|
| CI green (backend + frontend) | ✅ |
| 15 tests, 38 assertions, 0 failures | ✅ |
| Evidence screenshots committed | ✅ |
| README finalised | ✅ |
| Slack integration | ❌ OAuth blocked — documented honestly |
| All commits by agents, merges by human | ✅ |
| EastRouter used for all model calls | ✅ (z-ai/glm-5.1) |

---

## Learnings

- PHP version pinning in CI should always match the dev environment — pin both in `.tool-versions` or `.php-version` for future projects.
- Composer lock files are environment-sensitive; regenerating on a newer PHP than CI targets is a silent footgun.
- Honest failure documentation (Slack) is better than fabricated proof — handbook explicitly says this.