# SRP Search — WordPress Plugin
**College of Wooster — Senior Research Project Search**
Version 2.0.0

A Gutenberg block that allows visitors to search the IS_TITLES view in an external MSSQL database and display matching projects in a responsive table. Supports multi-field combinable search, pagination, bookmarkable URLs, and full mobile card layout.

---

## Requirements

- WordPress Multisite (network install, site activation)
- PHP 8.0+ with the **sqlsrv** and **pdo_sqlsrv** extensions installed for the **web server** PHP SAPI (not just CLI)
- Microsoft ODBC Driver 18 for SQL Server
- OpenSSL 1.1 or 3.0 (release build — not a dev build) on the web server
- MSSQL database accessible from the WordPress server on port 1433

---

## Server Setup

### Step 1 — Install Microsoft ODBC Driver 18 (SUSE Linux)

```bash
sudo zypper ar https://packages.microsoft.com/config/sles/15/prod.repo
sudo zypper --gpg-auto-import-keys refresh
sudo zypper install msodbcsql18 unixODBC-devel
```

### Step 2 — Install PHP sqlsrv extensions

```bash
sudo pecl install sqlsrv pdo_sqlsrv
```

If PECL fails, install PHP development headers first:
```bash
sudo zypper install php-devel
```

### Step 3 — Enable extensions for the web server PHP SAPI

Critical: PHP CLI and Apache mod_php use separate php.ini files. The extensions must be enabled in the web server ini, not just CLI.

```bash
# Find the correct ini file
php -i | grep "Loaded Configuration File"

# Add extension lines
echo "extension=sqlsrv.so"     >> /path/to/php.ini
echo "extension=pdo_sqlsrv.so" >> /path/to/php.ini
sudo systemctl restart apache2
```

### Step 4 — Install the issuer certificate

```bash
# Convert .cer to .pem if needed
openssl x509 -inform DER -in issuer.cer -out mssql-ca.pem

# Install on the WordPress server
sudo mkdir -p /etc/ssl/wooster
sudo cp mssql-ca.pem /etc/ssl/wooster/mssql-ca.pem
sudo chmod 644 /etc/ssl/wooster/mssql-ca.pem
sudo cp mssql-ca.pem /etc/pki/trust/anchors/
sudo update-ca-certificates
sudo systemctl restart apache2
```

### Step 5 — Create a read-only database user

```sql
CREATE LOGIN srp_readonly WITH PASSWORD = 'YourStrongPasswordHere';
USE [R18-DataOrch-PROD];
CREATE USER srp_readonly FOR LOGIN srp_readonly;
GRANT SELECT ON [dbo].[IS_TITLES] TO srp_readonly;
```

---

## Plugin Installation

### Step 1 — Add constants to wp-config.php

```php
define( 'SRP_DB_HOST',     'mssql2022.local.wooster.edu' );
define( 'SRP_DB_NAME',     'R18-DataOrch-PROD' );
define( 'SRP_DB_USER',     'srp_readonly' );
define( 'SRP_DB_PASSWORD', 'YOUR_PASSWORD_HERE' );
define( 'SRP_DB_ENCRYPT',  true );
// define( 'SRP_DEBUG_DB', true ); // debugging only — remove in production
```

### Step 2 — Upload the plugin

Upload the srp-search folder to /wp-content/plugins/ on the network server.

### Step 3 — Network Admin: do NOT network-activate

In Network Admin go to Plugins and leave the plugin installed but not network-activated.

### Step 4 — Site Admin: activate on the target site only

In the target site admin go to Plugins and activate SRP Search on that site only.

---

## Usage

1. Edit any page or post on the target site
2. Add the SRP Search block (found under Wooster Blocks in the block inserter)
3. Use the block toolbar to set alignment (Wide recommended)
4. Use the Inspector Panel to configure results per page, ordering, no-results message, and column visibility
5. Publish

### Search Fields

All fields are optional and combinable. At least one must be filled.

- Last Name: partial match
- Year: exact match, dropdown from DB
- Title Contains: partial match
- Major: exact match, dropdown from DB
- Advisor: partial match on last name

### Bookmarkable Searches

Search params are pushed to the URL (e.g. ?srp_last_name=smith&srp_year=2023). These URLs can be shared or bookmarked and the search re-runs automatically on page load.

---

## Cache Management

| Data | TTL |
|---|---|
| Years | 24 hours |
| Majors | 7 days |

Any WordPress admin page shows an SRP Search notice with a Clear cache now link. Click it after new projects are added to the database. Cache also clears on plugin deactivation.

---

## Debugging

Add to wp-config.php:
```php
define( 'SRP_DEBUG_DB', true );
```

Run from browser console on any page with the block:
```javascript
srp_run_diagnostic()
```

For production error logging without exposing errors to visitors:
```php
define( 'WP_DEBUG',         true );
define( 'WP_DEBUG_LOG',     true );
define( 'WP_DEBUG_DISPLAY', false );
```

Errors log to wp-content/debug.log.

---

## Security

- Credentials in wp-config.php only
- TLS encrypted DB connection with certificate verification
- Read-only DB user, SELECT on one view only
- Prepared statements on all queries
- Input length capped at 100 characters server-side
- Rate limiting: 30 search requests per IP per minute
- Nonce protection on all AJAX endpoints
- Stampede protection on cache population

---

## Responsive Layout

| Breakpoint | Form | Results |
|---|---|---|
| >= 750px | Single row | Full table |
| 641-749px | Two-row grid | Condensed table |
| <= 640px | Single column | Cards |

Print view hides the form and shows a clean full-width table with a College of Wooster header.

---

## Troubleshooting

- Database not configured: check wp-config.php constants
- Empty dropdowns: run srp_run_diagnostic() — check pdo_sqlsrv loaded in web SAPI
- Search unavailable: DB unreachable — check debug log, verify port 1433 access
- OpenSSL errors: confirm release build of OpenSSL 1.1 or 3.0 is installed
- pdo_sqlsrv in CLI but not web: add extension lines to web server php.ini and restart Apache
- Too many requests: rate limit (30/min) — resets after 60 seconds automatically
