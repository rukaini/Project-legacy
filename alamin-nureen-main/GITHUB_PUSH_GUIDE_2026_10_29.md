# TrainHub Al Amin — How to push the 29 October 2026 updates

This archive contains all application source code (including the October 29 updates),
composer manifests, migrations and SQL patches. It intentionally excludes `.env`,
`vendor/`, generated uploads, certificate signatures, runtime files, and large database
snapshots. Copy the archive's `alamin-nureen/` folder **into your existing Git checkout**
so you retain `.git` and your private `.env`.

## Safe PowerShell workflow (Windows + XAMPP)

1. Back up your local folder and database. Keep your existing `.env` private.
2. Open PowerShell:

```powershell
cd C:\xampp\htdocs\alamin-nureen
git status
git remote -v
git branch --show-current
git pull --ff-only origin main
```

Do the pull **before** copying updated code. If `git status` has uncommitted work,
commit or back it up first, do not discard it.

3. Download this ZIP to `Downloads`, then extract it (the ZIP contains the
`alamin-nureen/` top-level directory):

```powershell
Expand-Archive -LiteralPath "$env:USERPROFILE\Downloads\TrainHub_Al_Amin_GitHub_Ready_2026-10-29.zip" -DestinationPath "C:\xampp\htdocs" -Force
cd C:\xampp\htdocs\alamin-nureen
```

This overwrites updated code files without removing your `.git` or existing `.env`.

4. Review what Git will commit:

```powershell
git status --short
git diff --check
git ls-files -- .env
git add -A
git diff --cached --check
git diff --cached --stat
git status --short
```

The `git ls-files -- .env` command should produce no output. Before committing,
check that no confidential records, photos, passwords, uploads, or database dumps
are included. `git add -A` stages all unignored changes, so inspect the list.
If anything private is staged, unstage it with `git restore --staged -- <path>`.
Remember: `.gitignore` does not stop tracking files that Git already tracks.

5. Commit and push:

```powershell
git commit -m "Update TrainHub enrollment, credit hours and attendance UI"
git push origin main
```

If `git push` is rejected, stop; do not force push. Inspect the branch and ask
for help resolving changes already on the remote.

## Database and server notes

- New tables `trainhub_staff_enrollments` and `trainhub_staff_training_orders`
  were already created in your `alamin_main_database` using DBeaver.
- Do not re-import the old full DB snapshots. Run incremental patches only when needed.
- Database contents do **not** get uploaded via `git push`.
- The remote server must pull and deploy the code separately; pushing to GitHub
  does not automatically update `cendekia.utem.edu.my:8082`.
- If vendor is missing, run `composer install` locally; do not commit vendor.
- Run `php artisan optimize:clear` after deployment and test the UI with
  the correct SSO login, MySQL connection and public assets.
- Live MySQL, QR image provider and full PHPUnit could not be verified here.
