# MediLink — Healthcare Management System

**A role-based healthcare management platform built with PHP, MySQL and JavaScript.**

MediLink is a web-based healthcare management system designed to connect patients, doctors, administrative staff and other healthcare professionals through a centralized platform.

The application provides separate dashboards and role-based access for seven user types. It includes patient and doctor management, appointments, medical records, prescriptions, billing, insurance, statistics and a rule-based symptom checker.

---

## 1. Project Overview

| Item | Description |
|---|---|
| Project name | MediLink |
| Project type | Healthcare Management System |
| Backend | PHP |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, JavaScript |
| Charts | Chart.js |
| UI libraries | Font Awesome, Inter font |
| Architecture | PHP-based web application with JSON APIs |
| User roles | 7 |

### User Roles

MediLink supports seven roles, each with its own dashboard and access permissions.

| Role | Dashboard / Purpose |
|---|---|
| Administrator | System administration and overall management |
| Doctor | Doctor workspace and patient-related operations |
| Patient | Patient dashboard and personal healthcare information |
| Staff | Front desk and operational activities |
| Financial | Billing and financial operations |
| Data Scientist | Data and analytics dashboard |
| Insurance | Insurance-related operations and claims |

Access to different operations is controlled through the application's authentication and authorization logic.

---

## 2. Key Features

### Authentication and User Management
- User login and logout.
- Patient self-registration.
- Password hashing and verification.
- Session-based authentication.
- Role-based dashboard redirection.
- Protected pages and API endpoints.

### Patient and Doctor Management
- Patient information management.
- Doctor information management.
- Appointment management.
- Prescription management.
- Medical record management.

### Administration and Finance
- Staff management.
- Billing management.
- Insurance claim management.
- Financial statistics and reports.

### AI and Analytics
- Rule-based symptom checker.
- Disease prediction records.
- Smart recommendations.
- Dashboard statistics and charts.

**Note:** The current symptom checker uses predefined rules in the PHP `$RULES` configuration. It should not be confused with a trained machine-learning diagnostic model.

### User Interface
- Shared dashboard layout.
- Sidebar navigation.
- Responsive page layouts.
- Light and dark themes.
- Reusable CRUD tables and forms.
- Chart visualizations.
- Toast notifications.

---

## 3. Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Backend processing and authentication |
| MySQL / MariaDB | Database management |
| HTML5 | Page structure |
| CSS3 | Interface styling |
| JavaScript | Frontend interactions and API communication |
| Chart.js | Dashboard charts |
| Font Awesome | Interface icons |
| Inter | Typography |

The project uses plain JavaScript and PHP. A frontend framework or JavaScript build process is not required.

---

## 4. Project Folder Structure

```text
MediLink/
│
├── index.html
│
├── config/
│   └── database.php
│
├── database/
│   └── medilink.sql
│
├── includes/
│   ├── auth.php
│   └── layout.php
│
├── api/
│   ├── crud.php
│   ├── stats.php
│   │
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── register.php
│   │
│   └── ai/
│       └── predict.php
│
├── dashboards/
│   ├── admin-dashboard.php
│   ├── doctor-dashboard.php
│   ├── patient-dashboard.php
│   ├── staff-dashboard.php
│   ├── financial-dashboard.php
│   ├── datascientist-dashboard.php
│   ├── insurance-dashboard.php
│   │
│   ├── patient-management.php
│   ├── doctor-management.php
│   ├── prescription-management.php
│   ├── billing-insurance.php
│   ├── ai-prediction.php
│   └── smart-recommendations.php
│
├── css/
│   └── style.css
│
├── js/
│   └── app.js
│
└── README.md
```

### Main File Responsibilities

| File | Description |
|---|---|
| `index.html` | Main login interface, role selection and patient registration |
| `config/database.php` | Database connection configuration |
| `includes/auth.php` | Authentication, sessions and helper functions |
| `includes/layout.php` | Shared dashboard sidebar and top navigation |
| `api/auth/login.php` | Login API and authentication |
| `api/auth/register.php` | Patient registration API |
| `api/auth/logout.php` | Logout and session termination |
| `api/crud.php` | Reusable database CRUD API |
| `api/stats.php` | Dashboard statistics and chart data |
| `api/ai/predict.php` | Rule-based symptom checker |
| `js/app.js` | API helpers, CRUD tables, charts, notifications and dark mode |
| `css/style.css` | Shared styling and light/dark themes |

---

## 5. Installation and Setup

### Requirements

- PHP 8.x recommended.
- MySQL or MariaDB.
- Apache server (XAMPP recommended).
- A modern web browser.

### Step 1 — Download or Clone the Project

```bash
git clone https://github.com/JobayerRahman001/Software_Project.git
```

Alternatively, download the repository as a ZIP file and extract it.

### Step 2 — Configure the Database

Start Apache and MySQL through XAMPP.

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Import the SQL file:

```text
database/medilink.sql
```

Alternatively, use the MySQL command line:

```bash
mysql -u root < database/medilink.sql
```

### Step 3 — Configure Database Connection

Open:

```text
config/database.php
```

Check the database host, username, password and database name.

Default configuration described in this project:

| Setting | Default |
|---|---|
| Host | localhost |
| Username | root |
| Password | Empty |
| Database | medilink |

Make sure the database name and credentials match your local MySQL configuration.

### Step 4 — Start the Application

**Option A: XAMPP**

Place the MediLink project folder inside:

```text
C:\xampp\htdocs\MediLink
```

Open:

```text
http://localhost/MediLink/
```

**Option B: PHP Development Server**

From the project root:

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000/
```

---

## 6. Demo Accounts

The project includes the following demo account naming convention.

**Password for the listed demo accounts:**

```text
password123
```

| Role | Demo Email |
|---|---|
| Administrator | admin@medilink.com |
| Doctor | doctor@medilink.com |
| Patient | patient@medilink.com |
| Staff | staff@medilink.com |
| Financial | financial@medilink.com |
| Data Scientist | datascientist@medilink.com |
| Insurance | insurance@medilink.com |

Use these credentials only if the corresponding accounts have been inserted into the database.

---

## 7. Database and CRUD Operations

MediLink uses a centralized CRUD API to manage supported database tables.

**API endpoint:**

```text
api/crud.php
```

Supported operations:

| HTTP Method | Operation |
|---|---|
| GET | Retrieve records |
| POST | Create a record |
| PUT | Update a record |
| DELETE | Delete a record |

### Available Tables

```text
patients
doctors
staff
appointments
medical_records
prescriptions
investigations
billing
insurance_claims
symptoms_input
disease_predictions
recommendations
health_reminders
```

### Example API Requests

Retrieve records:

```http
GET api/crud.php?table=doctors
```

Create a record:

```http
POST api/crud.php?table=doctors
```

Update a record:

```http
PUT api/crud.php?table=doctors&id=1
```

Delete a record:

```http
DELETE api/crud.php?table=doctors&id=1
```

Filter records:

```http
GET api/crud.php?table=billing&payment_status=Pending
```

The `$TABLES` configuration near the beginning of `api/crud.php` defines the supported tables and their access permissions.

---

## 8. How to Use the Reusable CRUD Table

The project includes a reusable JavaScript function called `crudTable()`.

It can generate a table with searching, filtering, CSV export, forms and database operations.

Example:

```javascript
crudTable({

    el: '#doctors',

    table: 'doctors',

    pk: 'doctor_id',

    title: 'Doctors',

    columns: [
        ['full_name', 'Name'],
        ['specialization', 'Specialization'],
        ['consultation_fee', 'Fee', 'money']
    ],

    fields: [
        ['first_name', 'First name *'],
        ['specialization', 'Specialization *'],
        ['gender', 'Gender', ['Male', 'Female']]
    ]

});
```

### Supported Field Types

| Field Type | Purpose |
|---|---|
| `text` | Text input |
| `number` | Numeric input |
| `date` | Date input |
| `time` | Time input |
| `email` | Email input |
| `textarea` | Multiline text |
| Array of options | Dropdown |
| `patient` | Patient lookup dropdown |
| `doctor` | Doctor lookup dropdown |

Adding `*` at the end of a field label makes that field required.

---

## 9. Authentication and Authorization

Authentication-related functionality is organized into:

```text
includes/auth.php
api/auth/login.php
api/auth/register.php
api/auth/logout.php
```

The application uses:

- Password hashing and password verification.
- PHP sessions.
- Session regeneration after successful login.
- Role identification.
- Protected pages.
- Role-specific dashboard redirects.

The `HOME` configuration in `includes/auth.php` defines the dashboard destination for each supported role.

The `$TABLES` configuration in `api/crud.php` controls which roles may perform database operations on supported tables.

---

## 10. Project Review Guide

The project is organized so that reviewers can inspect its main components independently.

We welcome feedback on the following areas:

| Review Area | Files to Explore | Suggested Review |
|---|---|---|
| Frontend and UI | `index.html`, `css/style.css` | Design, usability and responsiveness |
| Authentication | `includes/auth.php`, `api/auth/` | Login, registration, sessions and security |
| Database | `config/database.php`, `database/medilink.sql` | Schema, relationships and data integrity |
| Backend API | `api/crud.php`, `api/stats.php` | CRUD operations, validation and permissions |
| Patient and Doctor | Relevant dashboard and management files | Healthcare workflows |
| Finance and Insurance | Financial and insurance dashboards | Billing and claims operations |
| AI and Analytics | `api/ai/predict.php`, AI-related dashboards | Rule-based prediction and analytics |
| Shared Frontend | `js/app.js`, `includes/layout.php` | Reusability and frontend integration |

### Suggested Testing Checklist

- [ ] Patient registration works.
- [ ] Login works for all seven roles.
- [ ] Each role reaches its correct dashboard.
- [ ] Unauthorized dashboard access is prevented.
- [ ] CRUD operations work as expected.
- [ ] Search and filtering work.
- [ ] CSV export works.
- [ ] Dashboard statistics and charts load.
- [ ] Dark mode works.
- [ ] Responsive layouts work on mobile devices.
- [ ] Database validation and role permissions behave correctly.

---

## 11. Important Notes

- Use demo or test data when reviewing the project. Do not use real patient information.
- Database credentials should be configured locally and should not be committed to a public repository.
- Demo credentials should only be used for a seeded demonstration database.
- The project should be reviewed and tested in a local PHP/MySQL environment before considering a production deployment.
- The symptom checker currently uses predefined rules rather than a trained medical AI model.

---

## 12. Repository

**GitHub Repository:**

https://github.com/JobayerRahman001/Software_Project

The repository contains the project source code and its development history.

Reviewers are welcome to explore the source code, test available features and provide suggestions for improvement.

**Thank you for reviewing MediLink!**