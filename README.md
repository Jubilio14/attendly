# Attendly

Attendly is an employee attendance management system built with Laravel, Vue, Inertia.js, Tailwind CSS, and PostgreSQL.

This project provides role-based attendance features for admins and employees, including mobile-first employee attendance pages and installable PWA support.

## Tech Stack

- Laravel
- Vue.js
- Inertia.js
- Tailwind CSS
- PostgreSQL
- Laravel Excel
- Vite PWA

## Features

### Admin

- Dashboard summary
- Employee management
- Department management
- Work schedule management
- Daily attendance monitoring
- Manual attendance correction
- Attendance request approval and rejection
- Monthly attendance reports
- Employee attendance detail reports
- Excel export

### Employee

- Mobile-first employee interface
- Check-in and check-out
- Automatic late detection
- Automatic early checkout detection
- Attendance history with date range filter
- Attendance request submission:
  - Leave
  - Sick
  - Work From Home
  - Work From Client
- Profile update
- Change password
- Installable PWA support

## Attendance Rules

- Employees can check in once per day.
- Employees can check out only after checking in.
- Late status is calculated automatically based on the employee's work schedule and late tolerance.
- Early checkout is detected automatically when an employee checks out before the scheduled end time.
- Attendance requests must be approved by an admin before being applied to attendance records.
- Employee pages are designed with a mobile-first layout and can be installed to the home screen as a PWA.


## Screenshots

Screenshots will be added later.

## Author

Jubilio Karuna
