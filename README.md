# Smart Road Cleaning Management System

A dashboard prototype that allows supervisors to manage road cleaning operations, assign tasks to cleaners, track progress in real-time, and generate basic reports.

## 📐 System Architecture

### 1. Frontend (HTML, CSS, JS)
- Dashboard interface for supervisor role
- Four main tabs:
  - Dashboard overview
  - Cleaners Management (CRUD)
  - Task Assignments
  - Reports
- JavaScript handles tab navigation, modals, and AJAX updates
- Status badges are color-coded:
  - 🟢 `Cleaned`
  - 🟠 `In Progress`
  - 🔴 `Missed`

### 2. Backend (PHP & Python)
#### ✅ PHP
Handles core CRUD operations:
- `cleaners`: `add_cleaner.php`, `update_cleaner.php`, `delete_cleaner.php`
- `assignments`: `assign_task.php`, `update_assignment.php`, `delete_assignment.php`
- `notifications`: `add_notification.php`, `update_notification.php`, `delete_notification.php`

#### ✅ Python (FastAPI)
Used for advanced analytics:
- `/missed-areas`: Roads not cleaned in the last 24h
- `/daily-report`: Daily summary (cleaned, missed, pending)
- `/predict-delays`: Simulated prediction based on weather, traffic, etc.

## 🛠️ Tech Stack

| Layer              | Stack                        |
|--------------------|------------------------------|
| Frontend           | HTML5, CSS3, JavaScript      |
| Backend            | PHP (XAMPP), Python (FastAPI)|
| Database           | MySQL                        |
| •	Simulated map    | SQLite, Leaflet.js for maps  |


### 🔧 Unresolved Issues

| Feature                | Status    | Details                                                                 |
|------------------------|-----------|-------------------------------------------------------------------------|
| Update Assignment      | Not working | Update functionality for assignment status is currently non-functional. |
| Assign Task            | Not working | Modal opens, but submission may not trigger the `assign_task.php` correctly. |
| Add Notification       | Not working | Button opens modal but form submission may not insert into database.   |
| Edit Notification      | Not working | Edit modal not wired to backend `update_notification.php`.             |
| In Progress Color      | Not working | Status `in-progress` does not display orange badge as expected.        |

### Planned Fixes

- Refactor JavaScript to ensure form `onsubmit` behavior is triggered for all modals.
- Check `action` attributes in `<form>` tags to ensure they match correct backend scripts.
- Ensure `assign_task.php`, `update_assignment.php`, and `update_notification.php` are included and working.
- Ensure status class `.status-in-progress` has CSS styling applied (e.g., `background: orange`).

## 🧪 How to Run the Project

### 🔹 Prerequisites
- PHP 7+ (XAMPP or LAMP)
- MySQL
- Python 3.8+
- FastAPI: `pip install fastapi uvicorn`

### 🔹 PHP Setup
1. Copy project folder to `htdocs/`
2. Start Apache and MySQL in XAMPP
3. Import the database:
   - Go to `phpMyAdmin`
   - Import `smart_cleaning_db (1)`
4. Open in browser:
