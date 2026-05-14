## Enhanced Web-Based Hostel Management System (HMS)

This project implements an **enhanced web-based Hostel Management System** for optimising hostel operations around Ugandan universities and secondary schools, based on the proposal by **Namirembe Joymary Lwanga** and **Kulabako Shamirah**.

### Features
- **Role-based authentication** for Students, Hostel Administrators/Wardens, and University Management.
- **Hostel & room management**: hostels, room types, occupancy and allocation tracking.
- **Student self-service**: online room browsing and booking requests.
- **Real Pesapal integration**: student can pay from booking page, callback and IPN verification update payment status.
- **Dashboards & reports**: basic occupancy and payments overview.

### Tech Stack
- **Backend**: PHP (compatible with XAMPP)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5

### Getting Started
1. Create a MySQL database, e.g. `hms_db`.
2. Import the SQL schema from `database/schema.sql`.
3. Update database credentials in `config.php`.
4. Configure Pesapal keys in `config.php`:
   - `HMS_PESAPAL_ENV`
   - `HMS_PESAPAL_CONSUMER_KEY`
   - `HMS_PESAPAL_CONSUMER_SECRET`
   - `HMS_APP_URL` (must be publicly reachable for callback/IPN in production)
5. Start Apache and MySQL via XAMPP.
6. Visit `http://localhost/HMS/public/` in your browser.

### Deploy on Render (Docker)
1. Push this repository to GitHub (Render deploys from a Git repo).
2. In Render, choose **New +** -> **Blueprint** and select this repo.
3. Render will read `render.yaml` and create service `hms-app` using `HMS/Dockerfile`.
4. In Render service **Environment**, set:
   - `HMS_APP_URL` to your Render URL (e.g. `https://hms-app.onrender.com`)
   - `HMS_DB_HOST`, `HMS_DB_NAME`, `HMS_DB_USER`, `HMS_DB_PASS` (external MySQL provider credentials)
   - Pesapal and SMTP variables as needed for production
5. Import `database/schema.sql` into your MySQL database.
6. Redeploy and verify login at `/login.php`.

> Database note:
> - App now supports both MySQL and PostgreSQL connection settings.
> - For PostgreSQL on Render, set `HMS_DB_DRIVER=pgsql` and either:
>   - provide `HMS_DATABASE_URL` (recommended), or
>   - provide `HMS_DB_HOST`, `HMS_DB_PORT=5432`, `HMS_DB_NAME`, `HMS_DB_USER`, `HMS_DB_PASS`, `HMS_DB_SSLMODE=require`.
> - **PostgreSQL:** import `database/schema.postgresql.sql` once (not `schema.sql`, which is MySQL). From your machine:
>   `psql "<External Database URL from Render>" -f HMS/database/schema.postgresql.sql`
>   If you do not have `psql` on Windows, install [Node.js LTS](https://nodejs.org/), then: `cd HMS/scripts && npm install && node apply-postgres-schema.mjs "YOUR_POSTGRES_URL"`.
>   Or open Render Postgres → connect with any SQL client and run the file contents. Until this runs, you will see errors like `relation "users" does not exist`.
> - **CSRF / “CSRF verification failed” on Render:** the app uses PHP sessions for CSRF tokens. On PostgreSQL, sessions are stored in the `hms_sessions` table by default (`HMS_SESSION_STORE=auto`). The app reads `HMS_DATABASE_URL` or Render’s **`DATABASE_URL`** (same value when the DB is linked). On first use, `hms_sessions` is created automatically if missing; you can still run `database/migrations/010_hms_sessions.postgresql.sql` manually if you prefer.
> - Some PHP queries still use MySQL-style string quoting in SQL; if a page errors after schema load, note the file from the stack trace and we can align that query for PostgreSQL.

### Super administrator
Two accounts are seeded in `database/schema.sql` / `schema.postgresql.sql` (emails `shamirah0mar915@gmail.com` and `joymarynl203@gmail.com`). They use the **same** initial password: **`SuperAdmin2026!`**. Sign in at `login.php` like other staff, then use **Change password** as soon as possible.

If your database was created before this password was documented, run `database/migrations/011_super_admin_password.sql` (MySQL) or `011_super_admin_password.postgresql.sql` (PostgreSQL) once to align hashes.

### Test Seed (Recommended)
**Option A — browser (easiest):** open once in your browser (key must match `HMS_DEMO_SETUP_KEY` in `config.php`, default below):

`http://localhost/HMS/public/seed_demo.php?key=hms-demo-setup-2026`

Then go to Login and use the accounts shown on that page.

**Option B — command line:**

`C:\xampp\php\php.exe c:\xampp\htdocs\HMS\scripts\seed.php`

Default demo accounts (reset every time you run seed):
- University Admin: `university.admin@hms.local` / `Admin12345`
- Warden: `warden@hms.local` / `Warden12345`

If login still fails: confirm MySQL database name/user/password in `config.php` match phpMyAdmin, and that you imported `database/schema.sql` into that database.

### Pesapal Payment Flow
1. Student opens `My Bookings`.
2. For approved or checked-in bookings with outstanding balances, click **Pay with Pesapal**.
3. System creates a pending payment row, registers an IPN URL with Pesapal, and redirects to checkout.
4. `payment_callback.php` and `payment_ipn.php` verify transaction status from Pesapal and update local records.

### Alignment with Proposal
- Replaces **manual, paper-based hostel processes** with a **centralised web system**.
- Supports **real-time visibility** into occupancy and payments.
- Designed to later integrate with **local payment channels** (MTN Mobile Money, Airtel Money via Pesapal).

