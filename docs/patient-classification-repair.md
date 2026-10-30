# Patient classification repair

Legacy patients can have `NULL` or incorrect categories after normalization. The original student/faculty migrations copied information only for patients already classified as student/faculty, then another migration removed the old columns. Seeing a person in Faculty Integration search does not save that identity to an existing patient.

Deploy the repair command before running these commands in the production project's terminal. These are Artisan commands, not phpMyAdmin SQL. The default is a preview; only `--apply` writes patient changes.

## Review one affected faculty patient

```bash
php artisan patients:reconcile-classifications --patient=30 --source=faculty
```

Review the patient ID, current/proposed classification, changed field names, and match result. After verifying the preview against the faculty directory, apply that patient's repair:

```bash
php artisan patients:reconcile-classifications --patient=30 --source=faculty --apply
```

The command updates the existing patient and saves a missing faculty code when returned by the directory. A confirmed faculty match without a code can still restore the faculty classification. It does not invent missing codes or recreate data the API no longer supplies.

## Review other affected records

```bash
php artisan patients:reconcile-classifications
php artisan patients:reconcile-classifications --source=all
php artisan patients:reconcile-classifications --patient=30 --patient=45 --source=all
```

The default `local` source uses only saved student numbers, faculty codes, and explicit categories, without network calls. `faculty`, `student`, `cms`, and `all` also consult the selected directory services. `all` requests all three directories and checks their evidence for conflicts. Student directory retrieval follows the existing service's cache/fallback behavior. Re-run the reviewed scope with `--apply` to save changes.

Matches use normalized exact email, faculty identity-provider ID, or an existing student number, never a person's name. Multiple matches, conflicting category evidence, duplicate patient emails, different saved identifiers, and identifiers already linked to another patient are skipped for manual review. Explicit alumni, guest, and other categories outside student/faculty/administrative are preserved. An empty or unavailable requested directory stops the command before patient changes.

## CMS configuration and existing CMS patients

CMS requests use `services.ocms.base_url` and `services.ocms.api_key`, populated by the existing `OCMS_EXTERNAL_API_URL` and `OCMS_EXTERNAL_API_KEY` environment variables. After deploying the code and confirming these environment variables are set, rebuild the production configuration cache:

```bash
php artisan config:cache
```

No schema migration is required for these CMS fixes. Assign CMS Access now uses the API's `admin_uuid` as the external identifier. Connection/authentication failures show a load error and Retry instead of an empty-directory message. Successful assignment verifies the profile against the API and reuses an existing profile matched by canonical ID or exact email. Ambiguous local profiles require manual review.

Saving an assignment also creates or updates its local `users` account in the same transaction, so it appears in User Management immediately, subject to that page's role/status filters and viewing permissions. The selected CMS role and account status are applied to the account. Existing passwords, identity-provider subjects, and authentication tokens are preserved. New accounts have no local password; their real identity-provider subject is linked on first SSO login. Role changes or deactivation revoke existing sessions. An invalid verified email or failed account save rolls back the profile and access assignment as well.

For assignments saved before this provisioning fix, select the person again and save their verified assignment to create the missing local account. This reuses existing profiles and accounts; there is no need to delete the assignment or change the database schema.

Preview existing CMS patient repairs in the production terminal:

```bash
php artisan patients:reconcile-classifications --source=cms
php artisan patients:reconcile-classifications --patient=45 --source=cms
```

Replace `45` with the patient ID being reviewed. A unique exact email match can restore `administrative` classification. If that patient already has a matching local CMS profile, the command can also replace its legacy email-based external ID with the canonical UUID. It does not create profiles or access assignments. Review the output before repeating the selected scope with `--apply`.

Patient login also checks the CMS directory by exact email. Confirmed CMS patients display as Administrative Personnel, while saved student/faculty evidence retains the existing login precedence. Existing administrative classification survives API outages. Directory membership alone never grants DMS admin/dentist access; those permissions still require an explicit local assignment. A successful patient login can also correct the UUID of an existing matching CMS assignment. Staff accounts without a patient record are outside this patient repair command; re-saving their verified CMS assignment can correct a legacy external ID.

Only missing institutional fields are filled. Existing academic information is preserved. Account roles, credentials, patient IDs, and appointments are not changed. Each patient repair is transactional and re-evaluated before saving; failures roll back that patient's repair and return a failing command status, while earlier successful patients remain repaired. Re-running a completed repair makes no further writes when its data is unchanged.

This repairs existing records independently of login. The current OIDC patient sync already uses saved identifiers, matched faculty profiles, and incoming student/faculty categories. A production record that remains `NULL` after a successful patient SSO sync still needs deployment-version and login-log investigation. Repairing the data alone does not establish why the original mismatch occurred.
