-- phpMyAdmin SQL Dump
-- Database: `superpack_database`
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `superpack_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounting_tasks`
--

CREATE TABLE `accounting_tasks` (
  `id` int(11) NOT NULL,
  `task` text NOT NULL,
  `owner` text NOT NULL,
  `status` text NOT NULL,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completion` int(11) NOT NULL,
  `priority` text NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistics_tasks`
--

CREATE TABLE `logistics_tasks` (
  `id` int(11) NOT NULL,
  `task` text NOT NULL,
  `owner` text NOT NULL,
  `status` text NOT NULL,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completion` int(11) NOT NULL,
  `priority` text NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proddev_tasks`
--

CREATE TABLE `proddev_tasks` (
  `id` int(11) NOT NULL,
  `task` text NOT NULL,
  `owner` text NOT NULL,
  `status` text NOT NULL,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completion` int(11) NOT NULL,
  `priority` text NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchasing_tasks`
--

CREATE TABLE `purchasing_tasks` (
  `id` int(11) NOT NULL,
  `task` text NOT NULL,
  `owner` text NOT NULL,
  `status` text NOT NULL,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completion` int(11) NOT NULL,
  `priority` text NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_tasks`
--

CREATE TABLE `sales_tasks` (
  `id` text NOT NULL,
  `task` text NOT NULL,
  `owner` text NOT NULL,
  `status` text NOT NULL,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completion` int(11) NOT NULL,
  `priority` text NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_tasks`
--

CREATE TABLE `warehouse_tasks` (
  `id` int(11) NOT NULL,
  `task` text NOT NULL,
  `owner` text NOT NULL,
  `status` text NOT NULL,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completion` int(11) NOT NULL,
  `priority` text NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `basic_pay` decimal(10,2) NOT NULL,
  `ot_pay` decimal(10,2) NOT NULL,
  `late_deduct` decimal(10,2) NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `sss_deduct` decimal(10,2) NOT NULL,
  `pagibig_deduct` decimal(10,2) NOT NULL,
  `total_deduct` decimal(10,2) NOT NULL,
  `net_salary` decimal(10,2) NOT NULL,
  `date_created` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `additional_pay`
--

CREATE TABLE `additional_pay` (
  `id` int(11) NOT NULL,
  `ot_pay` decimal(10,2) NOT NULL,
  `late_deduct` decimal(10,2) NOT NULL,
  `date_created` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `worker_evaluations`
--

CREATE TABLE `worker_evaluations` (
  `id` text NOT NULL,
  `employee_id` text NOT NULL,
  `name` text NOT NULL,
  `position` text NOT NULL,
  `department` text NOT NULL,
  `start_date` date NOT NULL,
  `comments` text NOT NULL,
  `performance` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_records`
--

CREATE TABLE `employee_records` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `position` text NOT NULL,
  `address` text NOT NULL,
  `phone_number` text NOT NULL,
  `age` text NOT NULL,
  `email` text NOT NULL,
  `shift` text NOT NULL,
  `salary` text NOT NULL,
  `status` text NOT NULL,
  `photo` text,
  `start_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `role` text NOT NULL,
  `time_in` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_request`
--

CREATE TABLE `leave_request` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `leave_type` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL,
  `department` text NOT NULL,
  `employee_id` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `qr_code_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL UNIQUE,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `department` text NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warning_notices`
--

CREATE TABLE `warning_notices` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `warning_type` varchar(255) NOT NULL,
  `warning_details` text NOT NULL,
  `warning_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_programs`
--

CREATE TABLE `training_programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_training`
--

CREATE TABLE `employee_training` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `training_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `certification_status` varchar(50) NOT NULL,
  `completion_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting_tasks`
--
ALTER TABLE `accounting_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `additional_pay`
--
ALTER TABLE `additional_pay`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_records`
--
ALTER TABLE `employee_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_training`
--
ALTER TABLE `employee_training`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_request`
--
ALTER TABLE `leave_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logistics_tasks`
--
ALTER TABLE `logistics_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proddev_tasks`
--
ALTER TABLE `proddev_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchasing_tasks`
--
ALTER TABLE `purchasing_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `warehouse_tasks`
--
ALTER TABLE `warehouse_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `warning_notices`
--
ALTER TABLE `warning_notices`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounting_tasks`
--
ALTER TABLE `accounting_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_pay`
--
ALTER TABLE `additional_pay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_records`
--
ALTER TABLE `employee_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_training`
--
ALTER TABLE `employee_training`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_request`
--
ALTER TABLE `leave_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logistics_tasks`
--
ALTER TABLE `logistics_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proddev_tasks`
--
ALTER TABLE `proddev_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchasing_tasks`
--
ALTER TABLE `purchasing_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_programs`
--
ALTER TABLE `training_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouse_tasks`
--
ALTER TABLE `warehouse_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warning_notices`
--
ALTER TABLE `warning_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;