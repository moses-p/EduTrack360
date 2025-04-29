-- Create database
CREATE DATABASE IF NOT EXISTS edutrack360;
USE edutrack360;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'parent', 'staff', 'ceo') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admission_number VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    class_id INT,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subjects table
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    is_ple_subject BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Teachers table
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT,
    is_class_teacher BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Staff table
CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    department VARCHAR(50) NOT NULL,
    position VARCHAR(50) NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_type ENUM('teacher', 'student', 'staff') NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Teacher Duty Roster
CREATE TABLE teacher_duty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    week_number INT NOT NULL,
    year INT NOT NULL,
    duty_type ENUM('morning', 'afternoon', 'evening') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
);

-- Events and Activities
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(100),
    event_type ENUM('academic', 'sports', 'cultural', 'religious', 'other') NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Salary Payments
CREATE TABLE salary_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('bank', 'cash', 'mobile_money') NOT NULL,
    reference_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- CEO Reports
CREATE TABLE ceo_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    report_type ENUM('academic', 'financial', 'attendance', 'events', 'overall') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    content TEXT NOT NULL,
    metrics JSON,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Exam results table
CREATE TABLE exam_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL,
    marks INT NOT NULL,
    grade VARCHAR(2),
    remarks TEXT,
    is_ple BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Scanned papers table
CREATE TABLE scanned_papers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    exam_type ENUM('midterm', 'endterm', 'ple') NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'processed', 'error') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL,
    total_marks INT NOT NULL,
    average_marks DECIMAL(5,2) NOT NULL,
    position INT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- System logs table
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level ENUM('info', 'warning', 'error', 'debug') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    source VARCHAR(100),
    user_id INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sessions table
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    data TEXT,
    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tasks table
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    assigned_to INT,
    assigned_by INT NOT NULL,
    due_date DATE,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- OCR Attempts table to track and improve OCR accuracy
CREATE TABLE ocr_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    ocr_text TEXT,
    possible_marks JSON,
    selected_mark INT,
    confidence DECIMAL(5,2),
    feedback ENUM('correct', 'incorrect', 'adjusted') DEFAULT NULL,
    feedback_mark INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create indexes for better performance
CREATE INDEX idx_student_class ON students(class_id);
CREATE INDEX idx_exam_results_student ON exam_results(student_id);
CREATE INDEX idx_exam_results_subject ON exam_results(subject_id);
CREATE INDEX idx_exam_results_class ON exam_results(class_id);
CREATE INDEX idx_reports_student ON reports(student_id);
CREATE INDEX idx_reports_class ON reports(class_id);
CREATE INDEX idx_attendance_user ON attendance(user_id);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_teacher_duty_week ON teacher_duty(week_number, year);
CREATE INDEX idx_events_date ON events(event_date);
CREATE INDEX idx_salary_payments_staff ON salary_payments(staff_id);
CREATE INDEX idx_salary_payments_date ON salary_payments(payment_date);
CREATE INDEX idx_ceo_reports_period ON ceo_reports(period_start, period_end);
CREATE INDEX idx_system_logs_level ON system_logs(level);
CREATE INDEX idx_system_logs_created_at ON system_logs(created_at);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_due_date ON tasks(due_date); 