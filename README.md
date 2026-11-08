# Hospital Management System

## Run locally with XAMPP

1. Start **MySQL** in the XAMPP Control Panel.
2. Keep using the configured database, `charles_hms`, if it already contains your working data. The application defaults to `localhost`, database `charles_hms`, user `root`, and an empty password.
3. From PowerShell, start the PHP server in the project folder:

   ```powershell
   Set-Location 'C:\Users\Timothy Talagtag\Documents\HMS\HospitalManagementSystem'
   $env:HMS_DB_HOST = 'localhost'
   $env:HMS_DB_NAME = 'charles_hms'
   $env:HMS_DB_USER = 'root'
   $env:HMS_DB_PASSWORD = ''
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t .
   ```

4. Open <http://127.0.0.1:8000/auth/role_selection.php>. Keep the PowerShell window open while using the app.

If you prefer Apache, configure it to serve this project directory, then open the same `/auth/role_selection.php` path through your local host.

## Database setup

The app's configured database is **`charles_hms`**. Do not drop or import the fresh schema over an existing database with data. The damaged **`charle_hms`** database is a different name and is not the app's default.

For a new, empty database only, create the database in phpMyAdmin, select it, choose **Import**, and import [`Database/charle_hms_schema.sql`](./Database/charle_hms_schema.sql). The file creates tables but does not create, select, or delete a database.

A fresh database needs an initial Admin account before staff can be invited; there is no public self-sign-up.

### Optional local demo accounts

For local development only, the seeder creates one account for each supported role. It is CLI-only, requires an explicit opt-in, accepts only a local database host, and refuses the known damaged `charle_hms` database. It never replaces an existing password. Do not use these shared demo credentials in production or expose a seeded development database.

Run from the project directory in PowerShell:

```powershell
$env:HMS_DB_NAME = 'charles_hms'
$env:HMS_DB_USER = 'root'
$env:HMS_DB_PASSWORD = ''
$env:HMS_SEED_DEMO = '1'
& 'C:\xampp\php\php.exe' 'Database\seed_demo_users.php'
Remove-Item Env:HMS_SEED_DEMO
```

The first successful run prints the credentials it created:

| Role | Email |
|---|---|
| Admin | `admin.demo@hms.local` |
| Doctor | `doctor.demo@hms.local` |
| Nurse | `nurse.demo@hms.local` |
| Pharmacist | `pharmacist.demo@hms.local` |
| Cashier | `cashier.demo@hms.local` |

All newly created demo accounts use password `HMS-Demo-Only!2026`. Sign in using the email and password on the matching role's login page. If an account already exists, the seeder skips its credentials and does not change its password. Rerunning the command does not create duplicates.

### Manually creating an initial admin

1. Generate a hash in PowerShell. PHP asks for the password without putting it in the command history:

   ```powershell
   & 'C:\xampp\php\php.exe' -r 'echo password_hash(readline("Initial admin password: "), PASSWORD_DEFAULT), PHP_EOL;'
   ```

2. In phpMyAdmin's SQL tab, replace `<PASTE_HASH_HERE>` with the hash and run:

   ```sql
   INSERT INTO users (username, full_name, email, password, role)
   VALUES ('admin', 'Hospital Admin', 'admin@example.com', '<PASTE_HASH_HERE>', 'Admin');

   INSERT INTO admin (UserID)
   VALUES (LAST_INSERT_ID());
   ```

3. Sign in at the Admin page. Use **Employees Management** to invite staff; invitees choose a username and password through the time-limited setup link.

## Email setup

Activation and password-reset emails use SMTP; PHP's local `mail()` function is not used. Configure these environment variables for the Windows account running Apache/PHP, then restart Apache:

| Variable | Example |
|---|---|
| `HMS_SMTP_HOST` | `smtp.gmail.com` |
| `HMS_SMTP_PORT` | `587` |
| `HMS_SMTP_ENCRYPTION` | `tls` |
| `HMS_SMTP_USERNAME` | Your SMTP account |
| `HMS_SMTP_PASSWORD` | Your provider's SMTP/app password |
| `HMS_SMTP_FROM_EMAIL` | The verified sender address |
| `HMS_SMTP_FROM_NAME` | `Hospital Management System` |

Keep SMTP credentials out of source control and do not share them in chat. If an invitation cannot be emailed, the Admin page displays a copyable, 24-hour activation link. Password reset requires working SMTP delivery.

For example, set the values in PowerShell before starting PHP:

```powershell
$env:HMS_SMTP_HOST = 'smtp.gmail.com'
$env:HMS_SMTP_PORT = '587'
$env:HMS_SMTP_ENCRYPTION = 'tls'
$env:HMS_SMTP_USERNAME = 'your-sender@example.com'
$env:HMS_SMTP_PASSWORD = 'your-provider-app-password'
$env:HMS_SMTP_FROM_EMAIL = 'your-sender@example.com'
$env:HMS_SMTP_FROM_NAME = 'Hospital Management System'
```

For XAMPP Apache, configure these as environment variables for the Windows account running Apache and restart Apache. If an older copy of the project contained an SMTP password, revoke it and use a newly generated credential.
