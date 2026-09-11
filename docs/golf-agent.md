# Golf agent: first release

The panel supports hotel-list questions, selected-hotel contract questions and text drafts. It has no business-record mutation tools. Contract document drafts still use the existing reviewed document import workflow. Other business modules, automatic execution, persistent conversation history and fine-tuning are not part of this first release.

Existing GolfAccess restrictions remain unchanged, including the live launch allow-list. Identity is server-derived; a request cannot choose its owner or administrator role. Local preview has an isolated local-preview owner.

Knowledge is stored outside the public directory at storage/app/private/golf-agent. Personal entries are visible only to their owner, including against other admins. Shared entries require an explicit administrator approval. Editing shared content invalidates approval. Disabling an entry excludes it from model context. Versions prevent stale edits. Mutation metadata is audited without API keys. DOCX/XLSX examples use the existing hardened document reader; extracted text must be reviewed and explicitly saved. Limit: 1000 knowledge entries, 6000 characters each, latest 30 eligible entries in model context.

Only hotel names and the selected hotel's contracts are sent with the question and eligible knowledge. Hotel contact information is excluded. Requests have time and size limits, chat is throttled, output is rendered as plain text, and response storage is disabled. Knowledge is reference material, never an authority for permissions or action execution.

Set GOLF_AGENT_ENABLED=false in server environment configuration to disable the feature. Refresh Laravel configuration cache if one is in use. Existing API key and model settings are reused; keys must never be committed.

Verification: scripts/php-local.ps1 tests/agent-smoke.php; node node_modules/vite/bin/vite.js build. API responses are mocked in local tests; a live request is required to confirm billing and provider access.
