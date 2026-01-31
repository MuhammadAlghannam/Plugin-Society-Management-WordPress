# Society Management

[![Version](https://img.shields.io/badge/version-2.0-blue.svg)](https://github.com)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759B?logo=wordpress)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php)](https://php.net/)

A comprehensive WordPress plugin for managing society memberships, registrations, email communications, and analytics.

**Author:** Muhammad Samir

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Shortcodes](#shortcodes)
- [Admin](#admin)
- [Database](#database)
- [File Structure](#file-structure)
- [Developer Guide](#developer-guide)
- [Changelog](#changelog)
- [Support](#support)
- [License](#license)

---

## Overview

Society Management is a full-featured WordPress plugin for professional societies and membership organizations. It handles member registration, renewals, email templates with placeholders, automated reminders, analytics, and certificate generation—all from a single admin interface.

| Capability              | Description                                                          |
| ----------------------- | -------------------------------------------------------------------- |
| **Member registration** | Frontend forms for new and student registration with file uploads    |
| **Profile & renewal**   | Member profile shortcode with certificate download and renewal flow  |
| **Analytics**           | Dashboard with Chart.js stats, payment breakdown, and trends         |
| **Email system**        | Templates, placeholders, SMTP, bulk send, and 11‑month reminders     |
| **Membership IDs**      | Configurable abbreviations and auto-generated membership numbers     |
| **Data**                | CSV import/export, membership history, and automatic expiry handling |

---

## Features

### User registration

- **Shortcode:** `[custom_signup]`
- Registration types: New Registration, Student Registration
- Fields: name, title, specialty, email, phone, DOB, addresses, password, membership type, payment method (InstaPay)
- File uploads: photo (2MB), CV (15MB), ID scans (20MB each), student card (2MB), payment receipt (80MB)
- Real-time email validation and duplicate check
- Client- and server-side validation, AJAX submit, SweetAlert2 feedback
- Redirect to login after success; country list via REST Countries API

### User profile

- **Shortcode:** `[profile_info]`
- Personal and membership details, status, certificate download (active/paid)
- Renewal: button when eligible, receipt upload, status → “In Review”, history logged

### Admin dashboard

- Totals: users, active/inactive, payment (Paid/Pending/Declined)
- Charts: membership type (pie), payment status (bar), registration trends (line, 12 months)
- Time filters: This month, Last month, This year
- Recent registrations table and growth metrics

### User management

- **Location:** Society Management → Membership Applications
- DataTables: search, sort, pagination; filters by membership type, payment status, user status
- Row click → profile modal (tabs: Personal, Membership, Files, Activity, Actions)
- Actions: Activate/Deactivate, Delete, Edit (WP user edit)
- CSV import/export, bulk email with template selector

### Membership numbers

- **Location:** Society Management → Generated ID Settings
- Format: `ABBREVIATION + 4-digit number` (e.g. `STU0001`)
- Per-type abbreviations and start numbers; bulk generate; migration from legacy `generated_ids` table

### Email templates

- **Location:** Society Management → Email Templates
- CRUD, duplicate, activate/deactivate, rich editor, preview
- Placeholders: `{full_name}`, `{email}`, `{phone}`, `{generated_id}`, `{membership_type}`, `{registration_type}`, `{payment_status}`, `{paid_date}`, `{expiry_date}`, `{institute}`, `{country}`, file URLs, `{files_table}`, and `{meta:KEY}`
- Single and bulk send; batch size 50

### Automated systems

- **11‑month reminders:** Daily cron; reminder 11 months after membership start; configurable template in Settings
- **Membership expiry:** Daily cron; set “Not Active” and “Declined” when expired; log to history (up to 100 per run)
- **Renewal confirmation:** Configurable template in Settings

### Settings

- **Location:** Society Management → Settings
- SMTP (e.g. Gmail + App Password), secure storage
- Reminder and renewal email template selection

---

## Requirements

- **WordPress** 5.0+
- **PHP** 7.4+
- **MySQL** 5.6+

---

## Installation

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin via **Plugins** in the WordPress admin.
3. Required database tables are created on activation.

---

## Quick Start

1. **Registration form**  
   Create a page and add the shortcode `[custom_signup]`.

2. **Member profile**  
   Create a page (e.g. “My profile”) and add `[profile_info]`.

3. **Admin**  
   Use **Society Management** in the admin menu: Dashboard, Membership Applications, Generated ID Settings, Email Templates, Settings.

4. **SMTP**  
   Configure SMTP under Society Management → Settings so emails send correctly.

---

## Shortcodes

| Shortcode         | Purpose                                                      |
| ----------------- | ------------------------------------------------------------ |
| `[custom_signup]` | Membership registration form (new + student).                |
| `[profile_info]`  | Logged-in user’s profile, certificate download, and renewal. |

---

## Admin

| Menu                        | Description                                                       |
| --------------------------- | ----------------------------------------------------------------- |
| **Dashboard**               | Analytics, charts, recent registrations.                          |
| **Membership Applications** | User list, filters, profile modal, CSV import/export, bulk email. |
| **Generated ID Settings**   | Membership number abbreviations and generation.                   |
| **Email Templates**         | Create/edit templates, placeholders, send single/bulk.            |
| **Settings**                | SMTP and reminder/renewal template selection.                     |

---

## Database

The plugin uses these tables (prefix `wp_` as per your install):

| Table                       | Purpose                                                                          |
| --------------------------- | -------------------------------------------------------------------------------- |
| `wp_csi_membership_numbers` | Membership number settings per type (replaces `wp_csi_generated_ids` in 2.0).    |
| `wp_csi_email_templates`    | Email template name, from, subject, body, placeholders, active flag.             |
| `wp_csi_email_reminders`    | Reminder log: user, template, type, sent_at, status.                             |
| `wp_csi_membership_history` | Event log: registration, renewal, status/payment changes, reminder sent, expiry. |

---

## File Structure

```
custom-signup-plugin/
├── custom-signup-plugin.php       # Main plugin file
├── uninstall.php
├── README.md
├── admin/
│   ├── assets/                    # Global admin CSS/JS
│   └── features/
│       ├── dashboard/             # Analytics
│       ├── users/                 # User list, profile modal, profile page
│       ├── membership-number/     # ID generation settings
│       ├── emails-templates/      # Template CRUD, placeholders, send
│       └── settings/              # SMTP and template config
├── shortcodes/
│   ├── registration/              # [custom_signup]
│   └── profile/                   # [profile_info]
├── includes/                      # DB, notifications, countries, FPDF/FPDI
├── ajax/
│   └── handlers.php
└── assets/                        # CSV template, PDF certificate template
```

---

## Developer Guide

### Constants

- `CSI_VERSION` — Plugin version
- `CSI_PLUGIN_DIR` / `CSI_PLUGIN_URL` / `CSI_PLUGIN_BASENAME` — Paths and basename

### Global JS (`CSI`)

```javascript
CSI.DataTables.init(selector, options);
CSI.Swal.success(title, text, timer);
CSI.Swal.error(title, text);
CSI.Bootstrap.showModal(selector);
CSI.Notify.success(message, useSwal);
// + destroy, confirm, loading, info, hideModal, error, warning, info
```

### PHP helpers (examples)

- Notifications: `csi_notify_success()`, `csi_notify_error()`, etc.
- Database: `CSI_Database::create_tables()`, `CSI_Database::get_table_name()`
- Users: `csi_get_admin_users()`, `csi_get_user_profile_data()`
- Templates: `csi_get_email_templates()`, `csi_replace_placeholders()`, `csi_send_template_email()`
- Membership IDs: `csi_get_abbreviation()`, `csi_assign_generated_id()`, `csi_get_user_membership_number()`
- History/expiry: `csi_log_membership_event()`, `csi_get_user_membership_history()`, `csi_is_membership_expired()`

### Hooks

- **Actions:** `csi_plugin_loaded`, `csi_daily_reminder_check`, `csi_daily_membership_expiry_check`
- **Filters:** `csi_load_assets_on_all_admin`, `csi_global_assets_loaded`

### Security

- Nonces on forms; `manage_options` for admin; sanitization and prepared statements; no direct file access.

### Frontend stack

- Bootstrap 5.3, DataTables 1.13.6, SweetAlert2 11.7, Chart.js 4.4 (dashboard), REST Countries API.

---

## Changelog

### 2.0

- Rebuild with modular admin/shortcode structure
- Analytics dashboard (Chart.js)
- Email template system and placeholders
- 11‑month reminder cron
- User profile modal and admin profile page
- CSV import/export
- Membership number system (replaces generated IDs)
- Global assets and notifications
- Registration UX: loading states, SweetAlert2, redirect after signup, email validation, student auto-type
- Renewal flow (frontend + admin)
- Membership history and timeline
- Daily membership expiry cron
- Settings: SMTP and reminder/renewal templates
- Migration from `generated_ids` to `membership_numbers`

---

## Support

For bugs, feature requests, or questions, please open an issue or contact the author.

---

## License

Proprietary. All rights reserved.
