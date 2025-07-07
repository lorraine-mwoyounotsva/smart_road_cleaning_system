# Edutec Global Interview Task - Smart Road Cleaning Management System

A dashboard prototype that allows supervisors to manage road cleaning operations, assign tasks to cleaners, track progress in real-time, and generate basic reports.

Here is a link to a video walkthrough of the website: https://drive.google.com/file/d/1FrcsTtBfoi0wpXbDKQ-3nAZjafhhAo2E/view?usp=sharing

## System Architecture

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
#### PHP
Handles core CRUD operations:
- `cleaners`: `add_cleaner.php`, `update_cleaner.php`, `delete_cleaner.php`
- `assignments`: `assign_task.php`, `update_assignment.php`, `delete_assignment.php`
- `notifications`: `add_notification.php`, `update_notification.php`, `delete_notification.php`

#### Python (FastAPI)
Used for advanced analytics:
- `/missed-areas`: Roads not cleaned in the last 24h
- `/daily-report`: Daily summary (cleaned, missed, pending)
- `/predict-delays`: Simulated prediction based on weather, traffic, etc.

## Tech Stack

| Layer              | Stack                        |
|--------------------|------------------------------|
| Frontend           | HTML5, CSS3, JavaScript      |
| Backend            | PHP (XAMPP), Python (FastAPI)|
| Database           | MySQL                        |
| Simulated map    | SQLite, Leaflet.js for maps  |


### Unresolved Issues

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

## How to Run the Project

### Prerequisites
- PHP 7+ (XAMPP or LAMP)
- MySQL
- Python 3.8+
- FastAPI: `pip install fastapi uvicorn`

### PHP Setup
1. Copy project folder to `htdocs/`
2. Start Apache and MySQL in XAMPP
3. Import the database:
   - Go to `phpMyAdmin`
   - Import `smart_cleaning_db (1)`
4. Open in browser

## APIs used

### Cleaner Management
| Endpoint | Method | Description | Parameters |
|----------|--------|-------------|------------|
| `get_cleaner.php` | GET | Fetch cleaner details | `id` (required) |
| `add_cleaner.php` | POST | Create new cleaner | Form data (name, email, shift, etc.) |
| `update_cleaner.php` | POST | Update cleaner profile | Form data with cleaner ID |

### Assignment Management
| Endpoint | Method | Description | Parameters |
|----------|--------|-------------|------------|
| `assign_task.php` | POST | Create new assignment | `cleaner_id`, `route_id`, `shift`, `date` |
| `get_assignment.php` | GET | Get assignment details | `id` (assignment ID) |
| `update_assignment.php` | POST | Modify assignment | `assignment_id`, `status`, etc. |
| `update_task.php` | POST | Update task status | `task_id`, `new_status` |

### Notification System
| Endpoint | Method | Description | Parameters |
|----------|--------|-------------|------------|
| `add_notification.php` | POST | Create notification | `title`, `message`, `priority`, `cleaner_id` (optional) |
| `get_notification.php` | GET | Get single notification | `id` (notification ID) |
| `get_notifications.php` | GET | List notifications | `user_id`, `limit` (optional) |
| `mark_notification_read.php` | POST | Mark as read | `notification_id` |
| `mark_all_read.php` | POST | Mark all as read | `user_id` |

### Route Management
| Endpoint | Method | Description |
|----------|--------|-------------|
| `get_routes.php` | GET | List all available routes |

### Reporting
| Endpoint | Method | Description |
|----------|--------|-------------|
| `report_issue.php` | POST | Submit issue report |
| `export_handler.php` | GET | Export data (CSV/Excel) |

### Authentication APIs
| Endpoint | Method | Description |
|----------|--------|-------------|
| `login.php` | POST | User authentication |
| `logout.php` | GET | Session termination |
| `login_register.php` | POST | New user registration |

- **Export API** (`export_api.py`): Handles data export functionality
- **Node Modules**: Various npm packages used for frontend development

## Assumptions made

1. Real-time GPS tracking is simulated using static coordinates
2. Authentication is basic; only role-based redirection is implemented
3. User passwords are pre-hashed using bcrypt
4. Leaflet.js is used for a placeholder map (no real GPS integration)
5. Frontend avoids frameworks (Vanilla JS for learning clarity)
6. Dummy information is used for supervisor and cleaners
7. Supervisors are computer-literate
8. No mobile interface needed for management
