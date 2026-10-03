# Casadive Villa Reservation System

Final Year Project (FYP) — a reservation website for Casadive Villa, covering
villa rooms and beach campsite packages. Built against the user scope in
`docs/Casadive Villa Reservation System (Proposal).pdf` (section 4.2):
Administrator, Staff, and Customer each get the duties defined there.

A working PHP/MySQL app: public pages, a booking form that writes real
bookings to the database, and role-gated Manager/Staff dashboards with account
and accommodation management. (The proposal's "Administrator" role is called
"Manager" in the running app — same role, friendlier name, since Admin/Staff
read as too similar.)

## Project structure

```
.
├── index.php                  Home page (quick booking bar + AI Assistant chatbot) — logic only
├── views/
│   └── index.view.php          HTML template for index.php
├── style/
│   └── index.css
├── assets/
│   ├── images/                 Static site imagery
│   ├── js/                     index.js, chatbot.js, review-photo.js (see "Views & JS" below)
│   ├── uploads/reviews/        Guest review photos that passed client-side AI verification (created at runtime)
│   └── uploads/payments/       QR payment proof/receipt uploads (created at runtime)
├── includes/                   Shared PHP (not web-facing content)
│   ├── db.php                  PDO connection to MySQL
│   ├── auth.php                Session + CSRF helpers: attempt_login(), current_user(), require_login(), csrf_field()/csrf_verify()
│   ├── helpers.php             format_status(), payment_needs_refund(), compute_stay_price(), recommend_accommodations()
│   ├── email_notify.php / mailer.php  Status-change emails (no Composer/PHPMailer dependency)
│   ├── header.php              Shared <head> + navbar for public pages
│   └── footer.php              Shared footer (+ WhatsApp card + review widget)
├── customer/                    Public-facing pages — logic files only, views/ holds their templates
│   ├── villa.php / campsite.php / gallery.php / contact_us.php / detail.php
│   ├── bookingform.php          Booking form -> inserts customer/booking/booking_item
│   ├── mybooking.php            Reference + phone lookup -> receipt, self-serve cancel, reviews
│   ├── payment.php / payment_method.php / sucess_payment.php
│   ├── chatbot_recommend.php / chatbot_booking_status.php  JSON endpoints for the AI Assistant (no view — pure API)
│   ├── views/                   One `<name>.view.php` per logic file above
│   └── style/                    CSS for the pages above
├── user/                        Manager/staff-facing pages — logic files only, views/ holds their templates
│   ├── login.php                 Real session-based auth against the `user` table
│   ├── logout.php
│   ├── admin_dashboard.php       role=manager only: bookings, accommodations, reviews, accounts (filename kept as-is; the role itself is "manager")
│   ├── manage_account.php        role=manager only: create/edit/delete staff & manager accounts
│   ├── manage_accommodation.php  role=manager only: create/edit/delete Villa & Campsite packages
│   ├── staff_dashboard.php       role=staff or manager: bookings, accommodation status, payments
│   ├── booking_receipt.php       role=manager or staff: view/print any booking's receipt
│   ├── notification.php          Builds a wa.me link, no view (redirect only)
│   ├── views/                   One `<name>.view.php` per logic file above
│   └── style/
│       ├── login.css
│       ├── dashboard.css
│       └── receipt.css
├── database/
│   ├── full_database.sql        MySQL schema + seed data
│   └── add_staff_tasks.sql      Add task assignment to an existing database
├── docs/                        Project deliverables (proposal, SRS, ERD, etc.)
└── README.md
```

**Logic/view split**: every page that renders HTML is split into a logic file (data-fetching,
POST handling, redirects — always resolves or `exit`s before any output) and a
`views/<name>.view.php` template (pure HTML/echo). The logic file ends with
`require __DIR__ . '/views/<name>.view.php';` in place of the markup. Pages with no HTML at all
(the two `chatbot_*.php` JSON endpoints, `logout.php`, `notification.php`) have no view file.
HTML `href`/`src` attributes inside a view file are unaffected by this split — they resolve
against the request URL (still the original, unchanged filename), not the view file's location
on disk; only PHP-side `include`/`require` paths needed an extra `../` to reach `includes/`.

Every top-level folder (root, `customer/`, `user/`) keeps its own `style/`
subfolder — no shared/cross-folder stylesheets. JavaScript follows the same idea:
`assets/js/index.js` and `assets/js/chatbot.js` are index.php-specific, while
`assets/js/review-photo.js` is shared (via an `initReviewPhotoWidget(ids)` call with different
element IDs) between the full review form on `mybooking.php` and the compact widget in
`footer.php`.

All internal links are relative — `index.php` sits at the site root, and
pages under `customer/` and `user/` link back to it with `../`.

## Getting started (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Import the schema: open phpMyAdmin (`http://localhost/phpmyadmin`) and run
  `database/full_database.sql`, or from a terminal:
   ```
  mysql -u root < database/full_database.sql
   ```
  This creates the `sabrisae_casadivevilla` database used in production, seeds 8
   Villa/Campsite accommodation packages, and creates two login accounts:

   | Username | Password | Role |
   |---|---|---|
   | `admin` | `Admin@12345` | manager |
   | `staff` | `Staff@12345` | staff |

   Both passwords are stored as bcrypt hashes — change them before any real
   deployment. `includes/db.php` connects as `root` with no password, XAMPP's
   default; edit it if your MySQL user differs.
    For an existing installation, run `database/add_staff_tasks.sql` to add the
    task table without recreating the database.
4. Visit `http://localhost/<project-folder>/index.php`.

## User scope (per the proposal) and what's built

**Administrator** (role name in the app: **manager**) — `user/admin_dashboard.php`, `manage_account.php`, `manage_accommodation.php`
- Add/update/delete Villa & Campsite packages (price, capacity, status) — `manage_accommodation.php`
- Assign tasks to active staff and track task status — inline on the dashboard
- Manage bookings and their status — inline on the dashboard
- Monitor payment status per booking (read-only column, sourced from `payment`)
- Manage staff **and manager** accounts, including changing her own username/
  password (a "My Account" shortcut in the topbar) — `manage_account.php`
- Manage customer records — implicit via the bookings list (customer rows are
  created through the booking flow, no separate customer accounts)
**Staff** — `user/staff_dashboard.php`, `booking_receipt.php`
- View/manage bookings, update status through the full proposal lifecycle:
  Pending → Confirmed → Checked-in → Checked-out, or Cancelled
- Verify/record payment status — a "Record a Payment" form writes to the
  `payment` table (deposit amount, status, receipt note), shown against each
  booking and in a running Recent Payments list
- View assigned tasks and update their status — inline on the dashboard
- Update room availability / record maintenance status — a status-only
  toggle per accommodation (no name/price/capacity edit access — that's
  manager-only)
- Generate booking confirmations and receipts — `booking_receipt.php`
  (print-ready, same layout as the customer-facing one)
- View customer booking information — the bookings table

**Customer** — `customer/*.php`
- Browse Villa/Campsite packages, check availability via the homepage's
  quick booking bar (real check-in/check-out/guests/type inputs that prefill
  `bookingform.php`) or a package's "Book now" button
- Make online bookings, receive a reference number and receipt
- Look up booking status any time via reference + phone
  (`customer/mybooking.php`) — no customer login exists, so this mirrors an
  airline "manage my booking" flow
- Contact the villa — `contact_us.php`

## How the pieces connect

- **Booking flow**: `customer/villa.php` / `campsite.php` "Book now" buttons
  deep-link into `customer/bookingform.php?accommodation=<package name>`.
  The homepage's quick booking bar instead passes `check_in`/`check_out`/
  `guests`/`type`, which prefill the form and (client-side) preselect the
  first package matching that type. The accommodation dropdown is always
  populated live from the `accommodation` table. On submit, the form
  validates server-side (dates, guest count vs. capacity), computes the
  total/deposit from the DB price, and inserts a `customer` + `booking` +
  `booking_item` row in one transaction.
- **Auth**: `user/login.php` checks credentials against `user.password`
  (bcrypt) via `password_verify()`, then stores a session in
  `$_SESSION['user']`. `includes/auth.php`'s `require_login($roles)` guards
  every manager/staff page and redirects/403s as appropriate.
- **CSRF**: every state-changing POST (login, booking submission, status
  updates, account/accommodation create-edit-delete, payment recording)
  carries a per-session token via `csrf_field()`/`csrf_verify()`.
- **Account management**: `admin_dashboard.php` links to
  `manage_account.php` (add/edit/delete) and `manage_accommodation.php`
  (add/edit/delete) — both `require_login(['manager'])`-gated; staff has no
  such link. Guardrails, enforced server-side (not just hidden in the UI):
  you can't delete your own account, can't delete/demote/deactivate the
  last remaining active manager, and editing your **own** account never
  accepts a role/status change even if the fields are tampered with —
  another manager has to do that.
- **MyBooking**: `customer/mybooking.php` looks a booking up by reference +
  phone and renders a receipt (guest/stay details, special request, price
  breakdown, deposit vs. balance due) with a `window.print()` button and
  print-specific CSS. `user/booking_receipt.php` is the same receipt for
  logged-in staff/manager, viewable by booking ID with no phone check needed.

## Database

See `database/full_database.sql` for the full schema (`user`, `staff_task`, `customer`,
`accommodation`, `booking`, `booking_item`, `payment`, `review`,
`notification_status`), based on `docs/ERD Casadive Villa Reservation
System.drawio.pdf`. `booking.booking_status` follows the proposal's exact
lifecycle: `pending`, `confirmed`, `checked_in`, `checked_out`, `cancelled`.
The `review` table exists in the schema but has no UI yet — it wasn't in the
proposal's five numbered system-scope modules, so it's a known gap rather
than a silent omission.

## Tech stack

- PHP 8 (PDO, prepared statements throughout)
- MySQL / MariaDB (via XAMPP)
- HTML5 / CSS3, vanilla JS (no framework)
- Google Fonts: Dancing Script, Mulish, Poppins, Raleway, Inter
