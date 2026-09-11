# Golf agent: first release

The panel supports hotel-list questions, selected-contract questions and text drafts. Chat remains read-only. Administrators can use the separate contract-action view to request a proposed change, inspect old/new values, explicitly confirm, and save. Contract document drafts still use the existing reviewed document import workflow. Other business modules, automatic execution, persistent conversation history and fine-tuning are not supported.

Contract actions are limited to one selected contract: allotment, guarantee, date fields, an existing per-person base price, or an existing accommodation row price. Deletion, activation, currency changes, catalog changes and arbitrary paths are rejected. Direct row prices become manual; base-price changes recalculate only automatic prices and show those dependent changes in the preview. Missing unit/per-person basis is never inferred.

The model returns a structured proposal with no executable tools. Server validation produces the authoritative diff and a signed/encrypted token bound to the current user, exact edits and hotel-record version, expiring after 15 minutes. Approval rechecks authorization, kill switch, expiry, version and validation inside a row-locked transaction. Replay and stale writes fail. The transaction stores the previous hotel snapshot and actor/action audit in setup_record_backups (kinds hotels and agent-contract-audit). Other open screens retain their old version so they cannot overwrite the action silently. No schema migration is required.

Existing GolfAccess restrictions remain unchanged, including the live launch allow-list. Identity is server-derived; a request cannot choose its owner or administrator role. Local preview has an isolated local-preview owner.

Knowledge is stored outside the public directory at storage/app/private/golf-agent. Personal entries are visible only to their owner, including against other admins. Shared entries require an explicit administrator approval. Editing shared content invalidates approval. Disabling an entry excludes it from model context. Versions prevent stale edits. Mutation metadata is audited without API keys. DOCX/XLSX examples use the existing hardened document reader; extracted text must be reviewed and explicitly saved. Limit: 1000 knowledge entries, 6000 characters each, latest 30 eligible entries in model context.

Only hotel names, contract summaries (or a selected contract) and eligible knowledge are sent for chat. Action proposals send only the selected contract (excluding sourceNotes) and the explicit request; learned preferences cannot trigger actions. Hotel contact information is excluded. Requests have time and size limits, endpoints are throttled, output is rendered as plain text, and response storage is disabled.

Set GOLF_AGENT_ENABLED=false in server environment configuration to disable the feature. Refresh Laravel configuration cache if one is in use. Existing API key and model settings are reused; keys must never be committed.

Verification: scripts/php-local.ps1 tests/agent-smoke.php; node node_modules/vite/bin/vite.js build. API responses are mocked in local tests; a live request is required to confirm billing and provider access.

Action tests: scripts/php-local.ps1 -d extension=pdo_sqlite tests/agent-contract-actions.php. These use an in-memory SQLite database and mocked OpenAI responses, never production data.
