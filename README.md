# MediLink - Healthcare Management System

PHP + MySQL + plain JavaScript. Seven roles (admin, doctor, patient, staff, financial, data scientist, insurance),
each with its own dashboard.

## Run it

1. Start MySQL (XAMPP or any MySQL/MariaDB) and import the database:
   `mysql -u root < database/medilink.sql`  (or import the file in phpMyAdmin)
2. Check `config/database.php` (default: user `root`, empty password, database `medilink`).
3. Put the folder in `htdocs`, or from this folder run `php -S localhost:8000`.
4. Open `http://localhost:8000/` and sign in.

Demo accounts (password for all: `password123`):
`admin@`, `doctor@`, `patient@`, `staff@`, `financial@`, `datascientist@`, `insurance@` + `medilink.com`

## Folder map

| Folder / file | What it does |
|---|---|
| `index.html` | Login page (choose role, sign in, patient sign-up) |
| `config/database.php` | Database connection |
| `includes/auth.php` | Session, login checks, small helpers |
| `includes/layout.php` | Sidebar + top bar shared by every page (menu is the `MENU` list) |
| `api/crud.php` | One API for all tables (list / create / update / delete) |
| `api/stats.php` | Numbers and chart data for dashboards |
| `api/ai/predict.php` | Symptom checker (simple rules in the `$RULES` list) |
| `api/auth/` | `login.php`, `logout.php`, `register.php` |
| `dashboards/*-dashboard.php` | One dashboard per role |
| `dashboards/*-management.php`, `billing-insurance.php`, `ai-prediction.php`, `smart-recommendations.php` | Shared management pages |
| `js/app.js` | `crudTable()` (reusable table + form), charts, toast, dark mode |
| `css/style.css` | All styling, light + dark theme (colours are the variables at the top) |

## How to add a table to a page

```js
crudTable({
  el: '#doctors', table: 'doctors', pk: 'doctor_id', title: 'Doctors',
  columns: [['full_name', 'Name'], ['specialization', 'Specialization'], ['consultation_fee', 'Fee', 'money']],
  fields:  [['first_name', 'First name *'], ['specialization', 'Specialization *'], ['gender', 'Gender', ['Male', 'Female']]],
});
```
A `*` at the end of a label makes the field required. Field type can be `date`, `time`, `number`, `textarea`,
a list of options, or `'patient'` / `'doctor'` for a dropdown loaded from the database.

## API

`GET/POST/PUT/DELETE api/crud.php?table=<name>[&id=<id>]` - tables: patients, doctors, staff, appointments,
medical_records, prescriptions, investigations, billing, insurance_claims, symptoms_input, disease_predictions,
recommendations, health_reminders. Any column can be used as a filter, e.g. `?table=billing&payment_status=Pending`.
Who may change which table is the `$TABLES` list at the top of `api/crud.php`.
