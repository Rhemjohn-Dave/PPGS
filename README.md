# TUP PPGS Task Management System

A comprehensive task management system for TUP-Visayas PPGS, built with PHP and SBAdmin2 template.

## Features

- User Authentication System
  - User registration with email and password
  - Login and logout functionality
  - Role-based access control (Admin, Staff, Users)

- Admin Features
  - Manage users (add, edit, delete)
  - Assign tasks to staff
  - Monitor task progress
  - View all tasks and their status

- Staff Features
  - View assigned tasks
  - Accept tasks and update status
  - Complete tasks and mark them as done
  - Track task progress

- User Features
  - Request tasks/services
  - Track request status
  - View assigned staff member
  - Monitor task progress

## Technical Stack

- Backend: PHP
- Frontend: SBAdmin2 (Bootstrap 4)
- Database: MySQL
- Server: XAMPP

## Prerequisites

- XAMPP (Apache, MySQL, PHP)
- Web browser (Chrome, Firefox, Safari, etc.)

## Installation

1. Clone or download this repository to your XAMPP's `htdocs` folder:
   ```
   C:\xampp\htdocs\tup-ppgs-tasks
   ```

2. Import the database schema:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `tup_ppgs_tasks`
   - Import the `database/schema.sql` file

3. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials if needed:
     ```php
     define('DB_SERVER', 'localhost');
     define('DB_USERNAME', 'root');
     define('DB_PASSWORD', '');
     define('DB_NAME', 'tup_ppgs_tasks');
     ```

4. Access the application:
   - Start XAMPP (Apache and MySQL)
   - Open your web browser
   - Navigate to: http://localhost/tup-ppgs-tasks

## Default Admin Account

- Username: admin
- Password: admin123

## Directory Structure

```
tup-ppgs-tasks/
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── vendor/
├── css/
├── js/
├── img/
├── index.php
├── login.php
├── register.php
├── logout.php
└── README.md
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements to prevent SQL injection
- Session management
- Input validation and sanitization
- Role-based access control

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact the TUP PPGS IT Department. 