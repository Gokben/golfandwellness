# Golf course deletion and persistence — 2026-09-03

Deployment update: published to `https://krpsoft.com.tr/golf/desktop` on 2026-09-03.
Live deletion, full reload, 16 remaining courses and the 17-row backup were verified.
See `live-release-status.md` for production backup paths and checks. The local-only
statements below describe the original implementation turn, before this deployment.

## Scope and cause

The course list was a module-level Vue ref populated from 17 hard-coded reference records. Delete only spliced that in-memory array, so a page reload recreated the deleted course. The old delete handler also depended on a native browser confirmation.

## Fix

- Both Golf Sahaları and Oyunlar now share the lazy MySQL-backed `golf-courses` store.
- The original reference records are initialization-only defaults. An existing or empty database list is never replaced with those defaults.
- Course list create, edit and delete use the existing optimistic-versioned setup-record API. Each update backs up the previous JSON list in `setup_record_backups`.
- API validation covers required names/codes, hotel text, unique codes and stable course keys. Renaming a code keeps the original `contractKey` for games/contracts.
- Delete requires an inline Vox-styled confirmation. Only a successful database response removes the visible row. Failed writes and conflicts leave the list intact and display an error.
- Added reload and loading/error states to both course list consumers. Related game and contract sets are not cascade-deleted.
- This change persists the basic course list, not the existing separate in-memory tee-time/closing/extra form sections.

## Local operation performed

On `http://127.0.0.1:8093/desktop.html`, deleted exactly the course with code/name `Zeynep Golf` through the new confirmation UI. The local MySQL set is now initialized, version 2, with 16 courses and zero rows having code `Zeynep Golf`. Version 1 (17 courses) was automatically backed up before deletion.

Carya's hotel text `Zeynep Golf, Regnum Carya` was intentionally preserved: it is not the deleted golf course row. Existing games/contracts were not modified. No source-site or production deployment/write was performed.

## Verification

- 16 Node tests passed (golf list persistence, course games, MySQL record client).
- 20 PHP feature tests passed, 228 assertions (record validation/backups/conflicts plus authentication).
- Vite production build passed; `git diff --check` clean.
- UI: delete success message, reload button, full page reload, reopen Golf Sahaları — still 16 records and no Zeynep Golf delete button.
- Read-only local API check: initialized=true, version=2, count=16, ZeynepCourseCount=0.
