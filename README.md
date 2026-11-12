# QR Generate

A web app for creating and managing QR codes. The home page lists saved projects. The builder is a separate page. Printed QR codes still open without signing in.

## Stack

- PHP 8.3+ and Laravel 13
- Vue 3 with Inertia
- Tailwind CSS 4
- SQLite by default, or MySQL through `.env`
- QR codes are drawn as SVG on the server. PNG downloads are rendered in the browser from that SVG.

## Run

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
npm install
composer run dev
```

Open http://127.0.0.1:8000. `composer run dev` starts the PHP server and Vite together.

The admin account is created by `database/seeders/AdminUserSeeder.php`. Sign in at http://127.0.0.1:8000/login. Google sign-in works only for an email that an admin has already added. Register `http://localhost:8000/login/google/callback` as an authorized redirect URI in Google Cloud. Production uses `https://qrcode.aerisbeaute.com/login/google/callback`.

To build the frontend without the Vite dev server:

```bash
npm run build
php artisan serve
```

## Pages

| Address | Purpose |
| --- | --- |
| `/login` | Sign in |
| `/` | Project list |
| `/create` | Create a QR |
| `/manage/{code}` | Scan analytics |
| `/manage/{code}/edit` | Edit a saved QR |
| `/links` | Short links |
| `/settings` | Password and users |
| `/q/{code}` | Public page when a QR is scanned |
| `/s/{code}` | Public short-link redirect |

## Smart QR

A smart QR stores a short link, so the destination can change without reprinting. Scans are counted on the project card.

## Tests

```bash
php artisan test
```
