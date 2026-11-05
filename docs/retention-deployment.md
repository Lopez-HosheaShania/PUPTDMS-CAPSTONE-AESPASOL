# Retention counting-basis update

This update changes patient-profile counting to the linked account's documented deactivation date. Document requests use approval or rejection dates. Policy effective dates remain separate from record starting dates. Review never archives or deletes records.

## Deploying to an existing database

1. Back up the database and current application release.
2. Check `php artisan migrate:status`. The existing retention tables, normalized approval table, and lifecycle-date migration `2026_10_29_000002_add_retention_review_rules_and_lifecycle_dates` must already be installed. Review any earlier pending migrations before deploying this update; do not reset or reseed production data.
3. Deploy the application code and built assets. Build with `npm ci` and `npm run build`, or upload the matching build output from your build machine. Include generated Sienna assets. Do not deploy `public/hot`.
4. Apply the new data migration and refresh the application caches:

```bash
php artisan migrate --path=database/migrations/2026_10_29_000003_update_retention_counting_bases.php --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The new migration makes no schema changes. It updates only patient policies using `patient_record_closed` and document-request policies using `document_request_closed`. It preserves policy IDs, periods, units, patient/request records, and historical dates. The changed policies receive a new version and return to Pending Review. Their superseded approvals are captured in the existing audit log before the current approval is removed, in the same transaction. Unaffected categories and already-updated policies are unchanged; rerunning the migration does not repeat the update.

Existing patient-closure evidence remains in its original normalized table for history. It is no longer a selectable counting basis. Do not drop that table or rewrite historical request statuses to install this update.

## Verification after deployment

- An authorized admin should confirm the two new counting rules, retain or edit the intended period, record the real clinic approval reference and policy effective date, and activate the new policy version.
- A patient with an active linked account should show **Keep record** and **Not started**. An inactive linked account with a documented deactivation date should display that date even when dependent records still need preservation.
- Profiles without linked accounts or historical deactivation evidence require review; the application does not infer dates from account creation, graduation, or general edits.
- Existing approved/ready/released document records use their recorded approval date. Historical rejections require their recorded decision/closure date. Missing dates require review; they are not filled with deployment time or `updated_at`.
- Generating/downloading a document does not restart its period. The current interface does not add a Release action.
- A due date is only eligibility for preservation review. Related records can still require retention, and the review screen provides no disposal action.

The data migration intentionally does not restore superseded approvals when rolling application code back. Preserve the audit history and resolve an incorrect configuration through a new reviewed policy change.
