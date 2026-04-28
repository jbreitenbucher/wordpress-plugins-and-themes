# SRP Search — WordPress Plugin
**College of Wooster — Senior Research Project Search**

A Gutenberg block that allows visitors to search the IS_TITLES view in the external MSSQL database and display matching projects in a responsive table.

---

## Requirements

- WordPress Multisite (network install, site activation)
- PHP 7.4+ with the **sqlsrv** PDO driver installed on the server
- The `sqlsrv` PHP extension: https://docs.microsoft.com/en-us/sql/connect/php/installation-tutorial-linux-mac
- MSSQL database accessible from the WordPress server

---

## Installation

### Step 1 — Install the PHP sqlsrv driver (if not already installed)

The server running WordPress needs Microsoft's PHP driver for SQL Server.

On Ubuntu/Debian:
```bash
# Install Microsoft ODBC driver first, then:
sudo pecl install sqlsrv pdo_sqlsrv
```

Verify with: `php -m | grep sqlsrv`

### Step 2 — Add constants to wp-config.php

Open `wp-config.php` on the WordPress server and add these lines **before** the `/* That's all, stop editing! */` line:

```php
// SRP Search — External MSSQL Database
define( 'SRP_DB_HOST',     'mssql2022.local.wooster.edu' );
define( 'SRP_DB_NAME',     'R18-DataOrch-PROD' );
define( 'SRP_DB_USER',     'srp_readonly' );        // read-only DB user
define( 'SRP_DB_PASSWORD', 'YOUR_PASSWORD_HERE' );
define( 'SRP_DB_ENCRYPT',  true );                  // set false only if SSL unavailable
```

### Step 3 — Create a read-only database user

On the MSSQL server, create a user with SELECT-only access to the view:

```sql
CREATE LOGIN srp_readonly WITH PASSWORD = 'YourStrongPasswordHere';
USE [R18-DataOrch-PROD];
CREATE USER srp_readonly FOR LOGIN srp_readonly;
GRANT SELECT ON [dbo].[IS_TITLES] TO srp_readonly;
```

### Step 4 — Upload the plugin

Upload the entire `srp-search` folder to:
```
/wp-content/plugins/srp-search/
```

### Step 5 — Network Admin: install (do NOT network-activate)

In the Network Admin dashboard:
- Go to **Plugins → Installed Plugins**
- Find **SRP Search** — it will appear but is not activated network-wide
- Do **not** click Network Activate

### Step 6 — Site Admin: activate on the target site

In the target site's admin dashboard:
- Go to **Plugins → Installed Plugins**
- Activate **SRP Search**

---

## Usage

1. Edit any page or post on the target site
2. Add the **SRP Search** block (found under Widgets)
3. Publish — the search form will appear on the front end
4. Visitors can search by any combination of: Last Name, Year, Title keyword, Major, Advisor

---

## Security Notes

- Credentials are stored in `wp-config.php`, never in the plugin or database
- The plugin uses a PDO prepared statement for all queries — SQL injection is not possible
- The DB user has SELECT-only access to one view
- The connection uses TLS encryption (`Encrypt=yes`) by default
- All AJAX requests are protected by WordPress nonces
- Input is sanitized via `sanitize_text_field()` before use

---

## Troubleshooting

**"Database not configured" error**
→ Check that all four `SRP_DB_*` constants are defined in `wp-config.php`

**"Search failed" error**
→ Confirm the PHP `sqlsrv` extension is installed: `php -m | grep sqlsrv`
→ Confirm the WordPress server can reach `mssql2022.local.wooster.edu` on port 1433
→ Confirm the `srp_readonly` user has SELECT access on `[IS_TITLES]`

**Major dropdown is empty**
→ Confirm `MAJOR_1` and `MAJOR_2` columns have data in the view
→ Check browser console for AJAX errors

**SSL/TLS connection errors**
→ Temporarily set `define( 'SRP_DB_ENCRYPT', false )` to test without SSL, then resolve the certificate issue
