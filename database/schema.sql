-- ============================================================
-- GYM MANAGEMENT SYSTEM - DATABASE SCHEMA & SEED DATA
-- Academic Project for B.E. Computer Science & Engineering
-- Database: MySQL 8+ / MariaDB compatible
-- ============================================================

CREATE DATABASE IF NOT EXISTS `gym_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gym_management`;

-- ------------------------------------------------------------
-- 1. Table: users (Base authentication & credentials)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `workouts`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `memberships`;
DROP TABLE IF EXISTS `membership_plans`;
DROP TABLE IF EXISTS `members`;
DROP TABLE IF EXISTS `trainers`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'trainer', 'member') NOT NULL DEFAULT 'member',
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. Table: trainers (Extended trainer profiles)
-- ------------------------------------------------------------
CREATE TABLE `trainers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `specialization` VARCHAR(150) NOT NULL,
    `joining_date` DATE NOT NULL,
    `bio` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_trainers_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. Table: members (Extended gym member profiles)
-- ------------------------------------------------------------
CREATE TABLE `members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
    `dob` DATE DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `emergency_contact` VARCHAR(30) DEFAULT NULL,
    `trainer_id` INT DEFAULT NULL,
    `join_date` DATE NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_members_trainer` FOREIGN KEY (`trainer_id`) 
        REFERENCES `trainers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. Table: membership_plans (Catalog of membership offerings)
-- ------------------------------------------------------------
CREATE TABLE `membership_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_name` VARCHAR(100) NOT NULL,
    `duration_days` INT NOT NULL COMMENT 'Duration in number of days',
    `price` DECIMAL(10,2) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. Table: memberships (Subscriptions assigned to members)
-- ------------------------------------------------------------
CREATE TABLE `memberships` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `plan_id` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('active', 'expired', 'pending', 'cancelled') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_memberships_member` FOREIGN KEY (`member_id`) 
        REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_memberships_plan` FOREIGN KEY (`plan_id`) 
        REFERENCES `membership_plans` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. Table: attendance (Check-in & check-out logs)
-- ------------------------------------------------------------
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `check_in_time` TIME NOT NULL,
    `check_out_time` TIME DEFAULT NULL,
    `status` ENUM('Present', 'Absent', 'Late') NOT NULL DEFAULT 'Present',
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_attendance_member` FOREIGN KEY (`member_id`) 
        REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. Table: payments (Billing & receipt transactions)
-- ------------------------------------------------------------
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `membership_id` INT DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `payment_method` ENUM('Cash', 'Card', 'UPI', 'Net Banking') NOT NULL DEFAULT 'Cash',
    `payment_status` ENUM('Paid', 'Pending', 'Failed') NOT NULL DEFAULT 'Paid',
    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payments_member` FOREIGN KEY (`member_id`) 
        REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_membership` FOREIGN KEY (`membership_id`) 
        REFERENCES `memberships` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. Table: workouts (Custom schedules & diet recommendations)
-- ------------------------------------------------------------
CREATE TABLE `workouts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `trainer_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days') NOT NULL DEFAULT 'Monday',
    `exercise_details` TEXT NOT NULL COMMENT 'Exercise names, sets, reps, weight suggestions',
    `diet_notes` TEXT DEFAULT NULL COMMENT 'Nutritional guidelines, calories, water intake',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_workouts_member` FOREIGN KEY (`member_id`) 
        REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_workouts_trainer` FOREIGN KEY (`trainer_id`) 
        REFERENCES `trainers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA FOR DEMO & COLLEGE VIVA PRESENTATION
-- Passwords:
-- Admin   : admin123
-- Trainer : trainer123
-- Member  : member123
-- Note: The system supports both hashed bcrypt passwords and initial plain text
-- fallback with automatic transparent upgrade to bcrypt on first login!
-- ============================================================

-- Insert Users (1 Admin, 3 Trainers, 6 Members)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `status`, `created_at`) VALUES
-- Admin
(1, 'Gym Administrator', 'admin@gym.com', 'admin123', 'admin', '+1 800-555-0199', 'active', '2026-01-01 09:00:00'),

-- Trainers
(2, 'Alex Mercer', 'alex@gym.com', 'trainer123', 'trainer', '+1 555-0112', 'active', '2026-01-05 10:00:00'),
(3, 'Sarah Jenkins', 'sarah@gym.com', 'trainer123', 'trainer', '+1 555-0113', 'active', '2026-01-10 10:00:00'),
(4, 'Mike Tyson', 'mike@gym.com', 'trainer123', 'trainer', '+1 555-0114', 'active', '2026-02-01 10:00:00'),

-- Members
(5, 'John Doe', 'john@gmail.com', 'member123', 'member', '+1 555-0201', 'active', '2026-02-10 11:00:00'),
(6, 'Emily Watson', 'emily@gmail.com', 'member123', 'member', '+1 555-0202', 'active', '2026-02-15 11:00:00'),
(7, 'Rahul Sharma', 'rahul@gmail.com', 'member123', 'member', '+91 98765-43210', 'active', '2026-03-01 11:00:00'),
(8, 'Priya Patel', 'priya@gmail.com', 'member123', 'member', '+91 98111-22334', 'active', '2026-03-05 11:00:00'),
(9, 'David Miller', 'david@gmail.com', 'member123', 'member', '+1 555-0205', 'active', '2026-03-10 11:00:00'),
(10, 'Ananya Rao', 'ananya@gmail.com', 'member123', 'member', '+91 99887-76655', 'inactive', '2026-01-15 11:00:00');

-- Insert Trainer Profiles
INSERT INTO `trainers` (`id`, `user_id`, `specialization`, `joining_date`, `bio`, `status`) VALUES
(1, 2, 'Strength & Hypertrophy Conditioning', '2026-01-05', 'Certified CSCS strength coach with 8+ years experience helping clients build muscle and athletic performance.', 'active'),
(2, 3, 'Yoga, Flexibility & Core Stability', '2026-01-10', 'RYT-500 Certified Yoga instructor specializing in posture alignment, mobility, and breathwork.', 'active'),
(3, 4, 'CrossFit, Boxing & High-Intensity HIIT', '2026-02-01', 'High-intensity conditioning coach focused on cardiovascular endurance, functional movement, and fat loss.', 'active');

-- Insert Member Profiles
INSERT INTO `members` (`id`, `user_id`, `gender`, `dob`, `address`, `emergency_contact`, `trainer_id`, `join_date`, `status`) VALUES
(1, 5, 'Male', '1998-05-14', '742 Evergreen Terrace, Springfield', '+1 555-9001 (Father)', 1, '2026-02-10', 'active'),
(2, 6, 'Female', '2001-08-22', '452 Elm Street, Metropolis', '+1 555-9002 (Sister)', 2, '2026-02-15', 'active'),
(3, 7, 'Male', '1995-11-03', 'Flat 402, Green Glen Layout, Bengaluru', '+91 99000-11223 (Spouse)', 1, '2026-03-01', 'active'),
(4, 8, 'Female', '1999-02-18', 'B-12 Hill View Residency, Mumbai', '+91 98222-33445 (Brother)', 3, '2026-03-05', 'active'),
(5, 9, 'Male', '1992-09-30', '109 Baker Street, London', '+1 555-9005 (Friend)', 1, '2026-03-10', 'active'),
(6, 10, 'Female', '2000-04-12', '12 Lake View Road, Hyderabad', '+91 97000-55443 (Mother)', NULL, '2026-01-15', 'inactive');

-- Insert Membership Plans
INSERT INTO `membership_plans` (`id`, `plan_name`, `duration_days`, `price`, `description`, `status`) VALUES
(1, 'Monthly Starter', 30, 40.00, 'Standard gym floor access, locker access, and basic orientation session.', 'active'),
(2, 'Quarterly Fitness', 90, 100.00, 'Full gym access, shower amenities, 1 complimentary trainer evaluation.', 'active'),
(3, 'Half-Yearly Pro', 180, 180.00, 'Unlimited floor access, sauna/steam bath access, personalized workout sheet.', 'active'),
(4, 'Annual Elite VIP', 365, 320.00, 'VIP access, personal locker, dedicated trainer consultation, and diet plans.', 'active');

-- Insert Memberships
INSERT INTO `memberships` (`id`, `member_id`, `plan_id`, `start_date`, `end_date`, `status`) VALUES
(1, 1, 4, '2026-02-10', '2027-02-10', 'active'),
(2, 2, 3, '2026-02-15', '2026-08-14', 'active'),
(3, 3, 2, '2026-03-01', '2026-05-30', 'active'),
(4, 4, 1, '2026-03-05', '2026-04-04', 'active'),
(5, 5, 2, '2026-03-10', '2026-06-08', 'active'),
(6, 6, 1, '2026-01-15', '2026-02-14', 'expired');

-- Insert Attendance Logs
INSERT INTO `attendance` (`member_id`, `attendance_date`, `check_in_time`, `check_out_time`, `status`, `notes`) VALUES
(1, '2026-03-18', '06:30:00', '08:00:00', 'Present', 'Morning Chest Routine'),
(2, '2026-03-18', '07:15:00', '08:30:00', 'Present', 'Yoga session completed'),
(3, '2026-03-18', '17:30:00', '19:00:00', 'Present', 'Evening Cardio & Arms'),
(4, '2026-03-18', '18:00:00', '19:15:00', 'Present', 'HIIT session'),
(5, '2026-03-18', '19:00:00', '20:10:00', 'Present', 'Leg day workout'),
(1, '2026-03-19', '06:45:00', '08:15:00', 'Present', 'Back and Biceps'),
(2, '2026-03-19', '07:30:00', '08:45:00', 'Present', 'Core Pilates'),
(3, '2026-03-19', '18:00:00', '19:30:00', 'Present', 'Shoulder workout'),
(1, '2026-03-20', '06:30:00', '08:00:00', 'Present', 'Leg Day'),
(2, '2026-03-20', '07:00:00', '08:15:00', 'Present', 'Morning Flow'),
(4, '2026-03-20', '18:30:00', '19:45:00', 'Present', 'Cardio interval'),
(1, '2026-03-21', '07:00:00', '08:30:00', 'Present', 'Arms & Forearms'),
(3, '2026-03-21', '09:00:00', '10:30:00', 'Present', 'Weekend Strength Session'),
(5, '2026-03-21', '10:00:00', '11:15:00', 'Present', 'General workout'),
(1, CURDATE(), '06:30:00', '08:00:00', 'Present', 'Active check-in today'),
(2, CURDATE(), '07:15:00', NULL, 'Present', 'Currently in gym'),
(3, CURDATE(), '08:00:00', '09:30:00', 'Present', 'Checked out');

-- Insert Payments
INSERT INTO `payments` (`id`, `member_id`, `membership_id`, `amount`, `payment_date`, `payment_method`, `payment_status`, `transaction_id`, `notes`) VALUES
(1, 1, 1, 320.00, '2026-02-10', 'Card', 'Paid', 'TXN-VIP-982103', 'Annual VIP Plan payment - processed via terminal'),
(2, 2, 2, 180.00, '2026-02-15', 'UPI', 'Paid', 'UPI/260215/887123', 'Half-Yearly Pro fee paid via GooglePay'),
(3, 3, 3, 100.00, '2026-03-01', 'Net Banking', 'Paid', 'NEFT-HDFC-991201', 'Quarterly Fitness membership transfer'),
(4, 4, 4, 40.00, '2026-03-05', 'Cash', 'Paid', 'CASH-REC-00104', 'Monthly Starter payment received at front desk'),
(5, 5, 5, 100.00, '2026-03-10', 'UPI', 'Paid', 'UPI/260310/443211', 'Quarterly Fitness payment via PhonePe'),
(6, 6, 6, 40.00, '2026-01-15', 'Cash', 'Paid', 'CASH-REC-00088', 'First month registration');

-- Insert Workouts & Diet Plans
INSERT INTO `workouts` (`member_id`, `trainer_id`, `title`, `day_of_week`, `exercise_details`, `diet_notes`) VALUES
(1, 1, 'Chest & Triceps Hypertrophy', 'Monday', 
'1. Barbell Bench Press: 4 sets x 8-10 reps (warmup + working sets)
2. Incline Dumbbell Press: 3 sets x 10-12 reps
3. Cable Chest Flyes: 3 sets x 15 reps
4. Tricep Rope Pushdowns: 4 sets x 12 reps
5. Skull Crushers: 3 sets x 10 reps', 
'Caloric Surplus: 2,800 kcal/day. 160g Protein. Consume 40g Whey Protein shake post-workout. Minimum 3.5 liters water daily.'),

(1, 1, 'Back & Biceps Power', 'Wednesday', 
'1. Conventional Deadlifts: 4 sets x 6 reps
2. Lat Pulldowns (Wide grip): 4 sets x 10 reps
3. Seated Cable Rows: 3 sets x 12 reps
4. Barbell Bicep Curls: 4 sets x 10 reps
5. Hammer Curls: 3 sets x 12 reps', 
'Carb intake higher on deadlift day: Oats + Bananas pre-workout 1 hour before training.'),

(1, 1, 'Leg Day & Calves', 'Friday', 
'1. Barbell Back Squats: 4 sets x 8 reps
2. Leg Press Machine: 4 sets x 12 reps
3. Romanian Deadlifts: 3 sets x 10 reps
4. Standing Calf Raises: 4 sets x 15 reps', 
'Post-leg day meal: Chicken breast or paneer with brown rice and mixed green veggies.'),

(2, 2, 'Mobility, Flexibility & Core', 'Tuesday', 
'1. Cat-Cow Pose: 3 sets x 10 breaths
2. Downward Dog to Cobra: 3 sets x 8 flows
3. Plank Hold: 4 sets x 45 seconds
4. Russian Twists: 3 sets x 20 reps
5. Glute Bridges: 3 sets x 15 reps', 
'Hydration focus with electrolyte water. Clean Mediterranean diet: salads, seeds, nuts, and lentils.'),

(3, 1, 'Upper Body Strength', 'Monday', 
'1. Overhead Military Press: 4 sets x 8 reps
2. Pull-ups / Assisted Pull-ups: 4 sets x 8 reps
3. Dumbbell Lateral Raises: 4 sets x 15 reps
4. Face Pulls: 3 sets x 15 reps', 
'Moderate carb, high protein. Eggs/Tofu, boiled sprouts, and whey isolate.');
