-- Insert default admin user (ID will likely be 1)
INSERT INTO users (username, password, role, full_name, email, phone) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@edutrack360.com', '1234567890');

-- Insert default teacher user (ID will likely be 2)
INSERT INTO users (username, password, role, full_name, email, phone) 
VALUES ('teacher', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Test Teacher', 'teacher@edutrack360.com', '1234567891');

-- Insert default CEO user (ID will likely be 3)
INSERT INTO users (username, password, role, full_name, email, phone) 
VALUES ('ceo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ceo', 'School CEO', 'ceo@edutrack360.com', '1234567892');

-- Insert default parent user (ID will likely be 4)
INSERT INTO users (username, password, role, full_name, email, phone) 
VALUES ('parent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Test Parent', 'parent@edutrack360.com', '1234567893');

-- Insert default staff user (ID will likely be 5)
INSERT INTO users (username, password, role, full_name, email, phone) 
VALUES ('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Test Staff', 'staff@edutrack360.com', '1234567894');

-- Insert a default class (ID will likely be 1)
INSERT INTO classes (name, year, term) 
VALUES ('Primary 1', 2024, '1');

-- Insert a default subject (ID will likely be 1)
INSERT INTO subjects (name, code, is_ple_subject)
VALUES ('Mathematics', 'MATH01', FALSE);

-- Link the default teacher (user_id=2) to the default class (class_id=1) and subject (subject_id=1)
INSERT INTO teachers (user_id, subject_id, class_id, is_class_teacher)
VALUES (2, 1, 1, TRUE); 