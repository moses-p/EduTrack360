-- Create student_classes table if it doesn't exist
CREATE TABLE IF NOT EXISTS `edutrack360`.`student_classes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `class_id` INT(11) NOT NULL,
  `enrolled_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('active', 'completed', 'withdrawn') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`, `class_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update classes table to include teacher_id if it doesn't have it
ALTER TABLE `edutrack360`.`classes` 
ADD COLUMN IF NOT EXISTS `teacher_id` INT(11) NOT NULL AFTER `term`,
ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `status`;

-- Health Records Table
CREATE TABLE IF NOT EXISTS `edutrack360`.`health_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `record_date` DATE NOT NULL,
  `health_status` ENUM('healthy', 'sick', 'injured', 'other') NOT NULL,
  `description` TEXT,
  `action_taken` TEXT,
  `recorded_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `recorded_by` (`recorded_by`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Discipline Records Table
CREATE TABLE IF NOT EXISTS `edutrack360`.`discipline_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `incident_date` DATE NOT NULL,
  `incident_type` ENUM('minor', 'major', 'severe') NOT NULL,
  `description` TEXT NOT NULL,
  `action_taken` TEXT,
  `reported_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `reported_by` (`reported_by`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Co-curricular Activities Table
CREATE TABLE IF NOT EXISTS `edutrack360`.`co_curricular_activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `status` ENUM('planned', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'planned',
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Participation Table
CREATE TABLE IF NOT EXISTS `edutrack360`.`activity_participation` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `activity_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `role` VARCHAR(100),
  `performance_notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participation` (`activity_id`, `student_id`),
  KEY `activity_id` (`activity_id`),
  KEY `student_id` (`student_id`),
  FOREIGN KEY (`activity_id`) REFERENCES `co_curricular_activities`(`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Financial Records Table
CREATE TABLE IF NOT EXISTS `edutrack360`.`financial_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `transaction_type` ENUM('tuition', 'other') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `due_date` DATE,
  `payment_date` DATE,
  `status` ENUM('pending', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending',
  `description` TEXT,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Passout Records Table
CREATE TABLE IF NOT EXISTS `edutrack360`.`passout_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `passout_date` DATE NOT NULL,
  `reason` TEXT,
  `final_remarks` TEXT,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reports Table
CREATE TABLE IF NOT EXISTS `edutrack360`.`reports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_type` ENUM('academic', 'attendance', 'health', 'discipline', 'co_curricular', 'financial', 'passout') NOT NULL,
  `period` ENUM('daily', 'weekly', 'monthly', 'yearly', 'custom') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `class_id` INT(11) NOT NULL,
  `student_id` INT(11),
  `generated_by` INT(11) NOT NULL,
  `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `report_data` JSON,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `student_id` (`student_id`),
  KEY `generated_by` (`generated_by`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`),
  FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create email_templates table if it doesn't exist
CREATE TABLE IF NOT EXISTS email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create payment_notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS payment_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    financial_record_id INT NOT NULL,
    template_id INT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    scheduled_date DATE NOT NULL,
    sent_date DATETIME,
    error_message TEXT,
    personalized_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (financial_record_id) REFERENCES financial_records(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE CASCADE
);

-- Create notification_settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS notification_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    days_before_due INT NOT NULL DEFAULT 7,
    reminder_frequency INT NOT NULL DEFAULT 3,
    max_reminders INT NOT NULL DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default email templates
INSERT INTO email_templates (name, subject, body, variables) VALUES
('payment_reminder', 'Payment Reminder: {amount} due on {due_date}', 
'Dear {student_name},

This is a reminder that you have a payment of {amount} due on {due_date} for {transaction_type}.

Payment Details:
- Amount: {amount}
- Due Date: {due_date}
- Transaction Type: {transaction_type}
- Description: {description}

Please ensure timely payment to avoid any late fees or penalties.

Best regards,
School Administration',
'["student_name", "amount", "due_date", "transaction_type", "description"]'),

('payment_overdue', 'URGENT: Overdue Payment of {amount}',
'Dear {student_name},

This is an urgent reminder that your payment of {amount} for {transaction_type} is overdue.

Payment Details:
- Amount: {amount}
- Due Date: {due_date}
- Transaction Type: {transaction_type}
- Description: {description}
- Days Overdue: {days_overdue}

Please make the payment as soon as possible to avoid any additional penalties.

Best regards,
School Administration',
'["student_name", "amount", "due_date", "transaction_type", "description", "days_overdue"]'),

('payment_confirmation', 'Payment Confirmation: {amount} received',
'Dear {student_name},

This is to confirm that we have received your payment of {amount} for {transaction_type}.

Payment Details:
- Amount: {amount}
- Payment Date: {payment_date}
- Transaction Type: {transaction_type}
- Description: {description}

Thank you for your prompt payment.

Best regards,
School Administration',
'["student_name", "amount", "payment_date", "transaction_type", "description"]');

-- Insert default notification settings
INSERT INTO notification_settings (days_before_due, reminder_frequency, max_reminders) 
VALUES (7, 3, 3);

-- Add indexes for better performance
DROP INDEX IF EXISTS idx_health_records_student_date ON health_records;
CREATE INDEX idx_health_records_student_date ON health_records(student_id, record_date);
DROP INDEX IF EXISTS idx_discipline_records_student_date ON discipline_records;
CREATE INDEX idx_discipline_records_student_date ON discipline_records(student_id, incident_date);
DROP INDEX IF EXISTS idx_activity_participation_student ON activity_participation;
CREATE INDEX idx_activity_participation_student ON activity_participation(student_id);
DROP INDEX IF EXISTS idx_financial_records_student_status ON financial_records;
CREATE INDEX idx_financial_records_student_status ON financial_records(student_id, status);
DROP INDEX IF EXISTS idx_passout_records_student ON passout_records;
CREATE INDEX idx_passout_records_student ON passout_records(student_id);
DROP INDEX IF EXISTS idx_reports_dates ON reports;
CREATE INDEX idx_reports_dates ON reports(start_date, end_date);
DROP INDEX IF EXISTS idx_payment_notifications_status ON payment_notifications;
CREATE INDEX idx_payment_notifications_status ON payment_notifications(status);
DROP INDEX IF EXISTS idx_payment_notifications_scheduled_date ON payment_notifications;
CREATE INDEX idx_payment_notifications_scheduled_date ON payment_notifications(scheduled_date);
DROP INDEX IF EXISTS idx_payment_notifications_student ON payment_notifications;
CREATE INDEX idx_payment_notifications_student ON payment_notifications(student_id);
DROP INDEX IF EXISTS idx_payment_notifications_record ON payment_notifications;
CREATE INDEX idx_payment_notifications_record ON payment_notifications(financial_record_id);
DROP INDEX IF EXISTS idx_payment_patterns_student ON payment_patterns;
CREATE INDEX idx_payment_patterns_student ON payment_patterns(student_id);
DROP INDEX IF EXISTS idx_payment_patterns_analysis ON payment_patterns;
CREATE INDEX idx_payment_patterns_analysis ON payment_patterns(last_analysis_date);
DROP INDEX IF EXISTS idx_attendance_student_date ON attendance;
CREATE INDEX idx_attendance_student_date ON attendance(student_id, date);
DROP INDEX IF EXISTS idx_behavior_student_date ON behavior_records;
CREATE INDEX idx_behavior_student_date ON behavior_records(student_id, date);
DROP INDEX IF EXISTS idx_book_borrowings_student ON book_borrowings;
CREATE INDEX idx_book_borrowings_student ON book_borrowings(student_id);
DROP INDEX IF EXISTS idx_book_borrowings_book ON book_borrowings;
CREATE INDEX idx_book_borrowings_book ON book_borrowings(book_id);
DROP INDEX IF EXISTS idx_student_transportation_student ON student_transportation;
CREATE INDEX idx_student_transportation_student ON student_transportation(student_id);
DROP INDEX IF EXISTS idx_transportation_attendance_student_date ON transportation_attendance;
CREATE INDEX idx_transportation_attendance_student_date ON transportation_attendance(student_id, date);
DROP INDEX IF EXISTS idx_teacher_attendance_date ON teacher_attendance;
CREATE INDEX idx_teacher_attendance_date ON teacher_attendance(date);
DROP INDEX IF EXISTS idx_teacher_duty_date ON teacher_duty;
CREATE INDEX idx_teacher_duty_date ON teacher_duty(duty_date);
DROP INDEX IF EXISTS idx_parent_visits_date ON parent_visits;
CREATE INDEX idx_parent_visits_date ON parent_visits(visit_date);
DROP INDEX IF EXISTS idx_uneb_performance_year ON uneb_performance;
CREATE INDEX idx_uneb_performance_year ON uneb_performance(year);

-- Create payment_patterns table
CREATE TABLE IF NOT EXISTS payment_patterns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    avg_days_before_due INT,
    avg_days_after_due INT,
    preferred_payment_day INT,
    payment_frequency VARCHAR(50),
    last_analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Add preferred_language column to students table if it doesn't exist
ALTER TABLE students
ADD COLUMN IF NOT EXISTS preferred_language VARCHAR(50) DEFAULT 'English' AFTER email;

-- Add indexes for better performance
DROP INDEX IF EXISTS idx_payment_patterns_student ON payment_patterns;
CREATE INDEX idx_payment_patterns_student ON payment_patterns(student_id);
DROP INDEX IF EXISTS idx_payment_patterns_analysis ON payment_patterns;
CREATE INDEX idx_payment_patterns_analysis ON payment_patterns(last_analysis_date);

-- Create attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Create behavior_records table
CREATE TABLE behavior_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    behavior_type ENUM('positive', 'negative', 'neutral') NOT NULL,
    description TEXT NOT NULL,
    date DATE NOT NULL,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create library_books table
CREATE TABLE library_books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(13),
    category VARCHAR(50),
    quantity INT NOT NULL DEFAULT 1,
    available_quantity INT NOT NULL DEFAULT 1,
    location VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create book_borrowings table
CREATE TABLE book_borrowings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    borrowed_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Create transportation_routes table
CREATE TABLE transportation_routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    route_name VARCHAR(100) NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    driver_contact VARCHAR(20),
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create route_stops table
CREATE TABLE route_stops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    route_id INT NOT NULL,
    stop_name VARCHAR(100) NOT NULL,
    stop_time TIME NOT NULL,
    stop_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES transportation_routes(id) ON DELETE CASCADE
);

-- Create student_transportation table
CREATE TABLE student_transportation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    route_id INT NOT NULL,
    stop_id INT NOT NULL,
    pickup_type ENUM('morning', 'afternoon', 'both') NOT NULL DEFAULT 'both',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES transportation_routes(id) ON DELETE CASCADE,
    FOREIGN KEY (stop_id) REFERENCES route_stops(id) ON DELETE CASCADE
);

-- Create transportation_attendance table
CREATE TABLE transportation_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    route_id INT NOT NULL,
    date DATE NOT NULL,
    pickup_status ENUM('present', 'absent', 'late') NOT NULL,
    dropoff_status ENUM('present', 'absent', 'late') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES transportation_routes(id) ON DELETE CASCADE
);

-- Teacher Attendance Table
CREATE TABLE teacher_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    check_in DATETIME,
    check_out DATETIME,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    UNIQUE KEY unique_teacher_date (teacher_id, date)
);

-- Teacher Duty Roster Table
CREATE TABLE teacher_duty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    duty_date DATE NOT NULL,
    duty_type ENUM('morning', 'afternoon', 'full_day') NOT NULL,
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

-- Parent Visits Table
CREATE TABLE parent_visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    parent_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    meeting_with VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (parent_id) REFERENCES users(id)
);

-- UNEB Performance Table
CREATE TABLE uneb_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    year INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    grade VARCHAR(2) NOT NULL,
    points INT NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY unique_student_subject_year (student_id, subject, year)
);

-- Create indexes for better performance
DROP INDEX IF EXISTS idx_teacher_attendance_date ON teacher_attendance;
CREATE INDEX idx_teacher_attendance_date ON teacher_attendance(date);
DROP INDEX IF EXISTS idx_teacher_duty_date ON teacher_duty;
CREATE INDEX idx_teacher_duty_date ON teacher_duty(duty_date);
DROP INDEX IF EXISTS idx_parent_visits_date ON parent_visits;
CREATE INDEX idx_parent_visits_date ON parent_visits(visit_date);
DROP INDEX IF EXISTS idx_uneb_performance_year ON uneb_performance;
CREATE INDEX idx_uneb_performance_year ON uneb_performance(year);

-- CREATE INDEX idx_reports_type_period ON reports(report_type, period); 