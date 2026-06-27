# Submission checklist -- Forge 2 / Edition 1 (PulseDesk)

Tick each and point to the in-repo path. Everything must be committed in THIS repo.

- [x] Repo is public, named forge2-arpit
      -> https://github.com/IamArpitpandey/forge2-arpit (public)

- [x] README has exact run steps; `php artisan migrate --seed` works from a fresh clone
      -> README.md verified locally

- [ ] Backend = Laravel 11 + MySQL ; Frontend = React 19 + Vite + Tailwind
      -> Laravel 13 used (not 11). PHP 8.5.4 advisory block on Laravel 11.
         Documented in README.md. Frontend React 19 + Vite + Tailwind correct.

- [x] Multi-tenancy: Org A cannot see Org B data (tenant derived from auth session)
      -> backend/tests/Feature/TenantIsolationTest.php

- [x] Hermes config committed -> agents/hermes/hermes-config.yaml (secrets redacted)

- [x] OpenClaw config committed -> agents/openclaw/openclaw.json (secrets redacted)

- [x] agent-log.md shows the real human->Hermes->OpenClaw loop
      -> agent-log.md (all prompts real, no fabrication)

- [x] sprints/ has >= 2 sprint docs
      -> sprints/sprint-01.md, sprints/sprint-02.md

- [ ] Slack proof in slack-export/
      -> OAuth bot token provisioning failed. Logged honestly in agent-log.md.

- [x] App / agents-running / CI screenshots in evidence/screenshots/
      -> 5 screenshots committed

- [x] .github/workflows/ci.yml present + a green run on the Actions tab
      -> Commit 5a76b1e, 15 tests, 38 assertions, 0 failures

- [x] PRs merged by ME (human); commit authors are the agents

- [x] All model calls went through EastRouter

- [x] Models used: z-ai/glm-5.1 (Hermes + OpenClaw) | Sprints run: 2

---

**Submitted by:** Arpit Pandey
**GitHub:** https://github.com/IamArpitpandey/forge2-arpit