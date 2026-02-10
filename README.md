# Employee Payroll Management System

A web-based application designed to streamline employee record management and payroll processing. Built with Core PHP and MySQL, the system features secure authentication, role-based access, and data export capabilities.

## Key Features

### Security & Authentication
* **Secure Login:** User authentication system utilizing BCrypt password hashing.
* **Session Management:** Protected routes ensuring only authenticated users can access sensitive data.
* **SQL Injection Protection:** Implements PDO Prepared Statements for all database interactions.
* **XSS Protection:** Input sanitization to prevent cross-site scripting attacks.

### Payroll & Data Management
* **Dashboard Overview:** Centralized view of payroll entries with dynamic pagination.
* **Advanced Search:** Filtering capability by Name, Payroll Number, or Month.
* **CRUD Operations:** Full capability to Create, Read, and Update employee records.
* **Data Export:** Functionality to export search results or full database records to CSV format.
* **Activity Logging:** Automated tracking of user login and logout actions.

## Technical Stack
* **Backend:** PHP 8+ (PDO Driver)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, JavaScript
* **Server:** Apache (XAMPP/WAMP environment)

## Application Previews

| Login Interface | Dashboard View |
|:---:|:---:|
| ![Login Screen](screenshots/login.png) | ![Dashboard View](screenshots/dashboard.png) |

## Installation Guide

### Prerequisites
* XAMPP (or similar local server environment like WAMP/MAMP).
* Git (optional, for cloning).

### Step 1: Clone the Repository
Navigate to your local server's root directory (e.g., `htdocs`) and clone this repository:
bash
git clone [https://github.com/asthre/payroll-management-system.git](https://github.com/asthre/payroll-management-system.git)

### Step 2: Database Setup
**Option A: Automatic Setup (Recommended)**
1.  Ensure Apache and MySQL are running in XAMPP.
2.  Open your web browser.
3.  Navigate to: `http://localhost/payroll-management-system/setup_database.php`
4.  This script will automatically create the database and the necessary tables.

**Option B: Manual Import**
1.  Open phpMyAdmin (`http://localhost/phpmyadmin`).
2.  Create a new database named `payroll_db`.
3.  Import the `payroll_db.sql` file included in this repository.

### Step 3: Access the Application
Open your web browser and visit:
`http://localhost/payroll-management-system`

## Default Credentials
Upon initial setup, use the following administrator credentials to log in:

* **Username:** kevin
* **Password:** Cl4$$iC

## Project Structure
* `index.php`: Main entry form for adding new payroll data.
* `view-records.php`: Dashboard for viewing, searching, and filtering records.
* `edit-record.php`: Interface for updating existing employee entries.
* `export-csv.php`: Backend logic for generating CSV reports.
* `setup_database.php`: Script to initialize database connection and default user.
