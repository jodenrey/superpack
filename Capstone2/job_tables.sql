-- SQL script to create job-related tables for the superpack_database

-- Create job_positions table
CREATE TABLE IF NOT EXISTS `job_positions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `requirements` TEXT NOT NULL,
    `status` ENUM('Open', 'Closed') DEFAULT 'Open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create job_applications table
CREATE TABLE IF NOT EXISTS `job_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `position_id` INT NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `address` TEXT NOT NULL,
    `education` TEXT NOT NULL,
    `experience` TEXT NOT NULL,
    `resume_path` VARCHAR(255),
    `application_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('New', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected') DEFAULT 'New',
    FOREIGN KEY (`position_id`) REFERENCES `job_positions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create candidates_training table
CREATE TABLE IF NOT EXISTS `candidates_training` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_id` INT NOT NULL,
    `candidate_name` VARCHAR(255) NOT NULL,
    `position` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `offer_status` ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending',
    `training_status` ENUM('Not Started', 'In Progress', 'Completed') NOT NULL DEFAULT 'Not Started',
    `scheduled_date` DATE,
    `completion_date` DATE,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert a sample job position
INSERT INTO `job_positions` (`title`, `department`, `description`, `requirements`, `status`) 
VALUES ('Software Developer', 'IT Department', 'Responsible for developing and maintaining software applications.', 
'Bachelor\'s degree in Computer Science or related field. Experience with PHP, JavaScript, and MySQL.', 'Open'); 