---
id: D-001
title: Hardening core free flow
status: done
completed: 2026-08-11
commit: 4945234
---

# D-001 — Free flow hardening

## Delivered

1. **`FreeFlowDemoSeeder`** — demo users + free orientation course + optional quiz
2. **E2E journey test** — register → enroll free → complete lessons → certificate list/verify/stream
3. **Empty states** — my learning, notifications, browse courses/paths, learner dashboard
4. **Course `is_paid` cast** — boolean/decimal pricing casts

## How to run demo

```bash
php artisan db:seed --class=FreeFlowDemoSeeder
# learner@enterlms.test / password
```

## Related

- `tests/Feature/Journey/FreeFlowCertificateJourneyTest.php`
- `database/seeders/FreeFlowDemoSeeder.php`
