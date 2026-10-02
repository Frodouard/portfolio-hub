# Portfolio Hub

Client portal and lead-management dashboard for a professional services firm
(accounting, tax, IT support). Visitors browse services and the team, submit
inquiries, and then sign in to track them. Staff get a separate admin dashboard
with live charts, inquiry triage, and announcements.

Built with plain PHP and MySQL — no framework, no build step.

## Features

**Public**
- Marketing site with services and team pages
- Inquiry form with server-side validation, stored per service
- Registration and login

**Customer dashboard**
- Summary stats and charts
- Personal inquiry history with status
- Profile fields for phone and location

**Admin dashboard**
- Live dashboard statistics (JSON endpoint + canvas charts)
- Inquiry triage: mark read, respond, close
- User administration including suspension
- Announcement publishing

## Requirements

- PHP 8.1 or newer (uses `str_starts_with`, `str_contains`, enums, PDO)
- MySQL 5.7+ / MariaDB 10.3+
- Apache or Nginx (XAMPP works out of the box)

## Setup

1. Clone the repository into your web root:

   ```bash
   git clone https://github.com/Frodouard/portfolio-hub.git
   ```

2. Create your local configuration:

   ```bash
   cp .env.example .env
   ```

   `.env` is gitignored. At minimum set `ADMIN_PASSWORD` to your own value.

3. Point Apache at the project directory and open it in a browser.

There is no separate install step: the database, tables, and migrations are
created automatically on the first request.

## Configuration

All settings are read from `.env` through the loader in `config.php`.

| Variable         | Default                 | Purpose                                  |
| ---------------- | ----------------------- | ---------------------------------------- |
| `DB_HOST`        | `localhost`             | MySQL host                               |
| `DB_PORT`        | `3306`                  | MySQL port                               |
| `DB_NAME`        | `portfolio_hub`         | Database name (auto-created)             |
| `DB_USER`        | `root`                  | Database user                            |
| `DB_PASS`        | *(empty)*               | Database password                        |
| `ADMIN_EMAIL`    | `admin@portfoliohub.com`| Seeded administrator email               |
| `ADMIN_PASSWORD` | *(unset)*               | Seeded administrator password            |

If `ADMIN_PASSWORD` is unset, no administrator is seeded. Set it to create the
first admin account, then remove it from `.env`.

## Project layout

```
config.php            .env loader and env() helper
db.php                PDO connection, creates database on first run
schema.php            table definitions and idempotent migrations
auth.php              user lookup, session helpers, role guards
login.php             customer sign-in
register.php          account creation
logout.php            session teardown
dashboard.php         customer dashboard
admin-login.php       staff sign-in
admin-dashboard.php   staff dashboard
admin-action.php      inquiry and user mutations (POST)
contact.php           inquiry submission endpoint (POST, JSON)
live-data.php         dashboard statistics endpoint (GET, JSON)
live-charts.php       chart rendering for the dashboard
index.php / team.php  public pages
database.sql          reference schema and seed data
data/                 legacy JSON store, gitignored
```

## Schema

Four tables: `users`, `inquiries`, `customers`, `announcements`.
Migrations in `schema.php` add any columns missing from an older database, so
upgrading an existing install needs no manual SQL.

## Security notes

- Passwords are hashed with `password_hash()` using the default algorithm.
- All user input reaches SQL through prepared statements.
- `require_login()` gates dashboard access and redirects users to the panel
  matching their own role.
- Credentials live in `.env`, which is never committed.

Before deploying: serve over HTTPS, set a strong `ADMIN_PASSWORD`, and keep
`.env` out of any copy of the project you publish.

## License

MIT — see [LICENSE](LICENSE).
