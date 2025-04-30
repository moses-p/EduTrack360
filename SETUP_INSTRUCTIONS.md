# EduTrack360 Setup Instructions

Follow these steps to finalize the EduTrack360 system setup:

## 1. Database Setup

1. Create the database schema:
   ```sql
   # Import the database schema
   mysql -u root -p < database/schema.sql
   
   # Import initial data
   mysql -u root -p < database/insert_default_users.sql
   ```

2. Configure database connection:
   - Open `config/database.php`
   - Update the database credentials if needed (username, password)

## 2. Server Configuration

1. Ensure your XAMPP/WAMP server is running
2. Make sure Apache and MySQL services are active
3. Place the project in your web server's document root (htdocs for XAMPP)

## 3. File Permissions

Ensure these directories have write permissions:
- `uploads/` (for student photos and scanned mark sheets)
- `reports/` (for generated PDF reports)

## 4. First Login

1. Access the system at: http://localhost/EduTrack360/
2. Log in with default admin credentials:
   - Username: `admin`
   - Password: `admin123`
3. **IMPORTANT**: Change the default password immediately after first login

## 5. System Configuration

1. Go to Settings and configure:
   - School information
   - Academic year
   - Current term
   - Grading scale

## 6. Data Setup

For a complete system, you'll need to set up:
1. Classes
2. Subjects
3. Teachers 
4. Students
5. Parents (to link with students)

## 7. OCR Configuration

For mark sheet scanning functionality:
1. Ensure Tesseract.js is properly installed
2. Test the OCR function with sample mark sheets in the teacher interface

## 8. Backup Strategy

Implement a regular backup schedule:
1. Database backup: `mysqldump -u root -p edutrack360 > backup_file.sql`
2. File backup: Copy the entire project folder regularly

## Support

For any issues or inquiries, contact the system owner:
- Email: mosesharuna407@gmail.com

© 2025 EduTrack360. All Rights Reserved. 