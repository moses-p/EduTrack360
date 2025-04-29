-- Create student_classes table if it doesn't exist
CREATE TABLE IF NOT EXISTS `edutrack360`.`student_classes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `class_id` INT(11) NOT NULL,
  `enrolled_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('active', 'completed', 'withdrawn') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`, `class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update classes table to include teacher_id if it doesn't have it
ALTER TABLE `edutrack360`.`classes` 
ADD COLUMN IF NOT EXISTS `teacher_id` INT(11) NOT NULL AFTER `term`,
ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `status`; 