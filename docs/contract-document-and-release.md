# Contract documents and release updates

The hotel contract screen accepts DOCX and XLSX files up to 4 MB. Legacy DOC/XLS
files must be saved in the newer Office format first. Image-only documents are
not supported. PHP zip, DOM and mbstring extensions are required.

The server extracts text locally and sends it to OpenAI Responses with a strict
JSON schema and store=false. It never executes document instructions or model
tools. Uploads are temporary; the reader removes its temporary file. No database
write occurs during extraction. The user reviews the draft, adds it to the hotel
form, and uses the existing Save command and record validation to persist it.

Set OPENAI_API_KEY in the private production .env and optionally set
OPENAI_CONTRACT_MODEL (default gpt-4.1-mini). Never place credentials in frontend
configuration or source control. Clear the Laravel configuration cache after
changing these settings. Without a key the UI reports the missing configuration.
Live OpenAI extraction has not been tested without an account key.

The application checks /api/app-release each minute and on returning to the tab.
It compares the loaded hashed JS filename to the current manifest. An open work
window, including minimized windows, blocks automatic reload conservatively.
The user saves and closes work windows before reload. No local storage, session,
or database records are cleared. Existing tabs predating this feature need one
ordinary refresh to load the release watcher for the first time.

The cPanel release copies the new controllers, API routes and service settings,
clears generated route/config caches, and adds no-store headers for HTML/PHP/JSON.
Old hashed assets are retained; the manifest is published after code and assets.
Previous overwritten files are backed up under private storage/deploy-backups.
Database changes and credentials are not part of this release.

Validation: php tests/contract-document-smoke.php; node --test
tests/release-policy.test.mjs tests/proposals.test.mjs; npm run build.
