# EduTrack360 - School Performance Management System

EduTrack360 is a comprehensive educational management system designed to streamline academic performance tracking, reporting, and administrative tasks for schools. The platform provides role-specific dashboards for administrators, teachers, parents, staff, and school CEOs.

## System Overview

EduTrack360 offers:

- **Multi-Role Access**: Different interfaces for administrators, teachers, parents, staff, and school executives
- **Mark Entry & Management**: Digital grade recording with OCR scanning capability
- **Attendance Tracking**: Student attendance monitoring
- **Report Generation**: Automated term reports and performance analytics
- **Student Management**: Comprehensive student records and class assignment
- **User Management**: Administrative control over system users
- **Analytics Dashboard**: Performance metrics and visualization

## User Roles

### Administrator
- Full system access and configuration
- User management
- Class and subject setup
- System logs monitoring
- OCR analysis tools
- Task management

### Teacher
- Mark entry and management
- Attendance recording
- Student management
- Report viewing
- Class management

### Parent
- View student reports
- Track academic progress
- Access term performance history

### Staff
- Limited administrative functions
- Support operations

### CEO/Executive
- Performance overview dashboard
- Analytics and reporting

## Technical Architecture

### Backend
- PHP 8.0+
- MySQL Database
- RESTful API endpoints

### Frontend
- HTML5, CSS3, JavaScript
- Responsive design for mobile compatibility
- Chart.js for data visualization

### Key Features
- OCR technology for mark scanning
- Role-based access control
- Automated report generation
- Performance analytics
- Data export capabilities

## System Requirements

- Web server with PHP 8.0+
- MySQL 5.7+ database
- Modern web browser
- XAMPP/WAMP for local development

## Installation

1. Clone the repository
2. Import database schema using `create_tables.sql`
3. Configure database connection in `config/database.php`
4. Set up virtual host or run through XAMPP/WAMP
5. Access the system through your web browser

## Directory Structure

```
EduTrack360/
├── admin/           # Admin-specific functions
├── ajax/            # AJAX request handlers
├── api/             # API endpoints
├── assets/          # CSS, JS, images
├── config/          # Configuration files
├── database/        # Database schemas and migrations
├── includes/        # Shared PHP functions
├── libs/            # External libraries
├── models/          # Data models
├── views/           # Frontend templates
│   ├── admin/       # Admin interface
│   ├── teacher/     # Teacher interface
│   ├── parent/      # Parent interface
│   ├── staff/       # Staff interface
│   ├── ceo/         # Executive interface
│   └── includes/    # Shared view components
└── index.php        # Main application entry point
```

## Security Features

- Role-based access control
- Session management
- Password hashing
- Input validation
- XSS protection
- CSRF protection

## License

This software is proprietary and owned by Ssemwanga Haruna Moses. See LICENSE.txt for full details.

© 2025 EduTrack360. All Rights Reserved.

For inquiries regarding licensing or usage, please contact: mosesharuna407@gmail.com 