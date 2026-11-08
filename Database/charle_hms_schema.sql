-- Fresh schema for the Hospital Management System PHP application.
-- In phpMyAdmin, select a NEW empty database before importing this file.
-- This file intentionally does not DROP tables or create/select a database.
-- Foreign keys are omitted because the current application deletes related
-- records explicitly and does not consistently enforce relational constraints.

SET NAMES utf8mb4;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `ContactNumber` varchar(30) DEFAULT NULL,
  `token` varchar(128) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `admin` (
  `AdminID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `superadmin` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`AdminID`),
  UNIQUE KEY `uq_admin_user` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `department` (
  `DepartmentID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentName` varchar(150) NOT NULL,
  `DepartmentBuilding` varchar(100) DEFAULT NULL,
  `DepartmentFloor` varchar(50) DEFAULT NULL,
  `DepartmentLocation` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`DepartmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `doctor` (
  `DoctorID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `DoctorName` varchar(200) NOT NULL,
  `Email` varchar(200) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `DoctorType` varchar(100) DEFAULT NULL,
  `DoctorFee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Availability` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`DoctorID`),
  KEY `idx_doctor_user` (`UserID`),
  KEY `idx_doctor_department` (`DepartmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `nurse` (
  `NurseID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `Name` varchar(200) NOT NULL,
  `Email` varchar(200) DEFAULT NULL,
  `Availability` varchar(100) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  PRIMARY KEY (`NurseID`),
  KEY `idx_nurse_user` (`UserID`),
  KEY `idx_nurse_department` (`DepartmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pharmacist` (
  `PharmacistID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `Name` varchar(200) NOT NULL,
  `Email` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`PharmacistID`),
  KEY `idx_pharmacist_user` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cashier` (
  `CashierID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `Name` varchar(200) NOT NULL,
  PRIMARY KEY (`CashierID`),
  KEY `idx_cashier_user` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `receptionist` (
  `ReceptionistID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `Name` varchar(200) DEFAULT NULL,
  `Email` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`ReceptionistID`),
  KEY `idx_receptionist_user` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `patients` (
  `PatientID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(200) NOT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `Sex` varchar(30) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `Contact` varchar(30) DEFAULT NULL,
  `PatientType` varchar(50) DEFAULT NULL,
  `NurseID` int(11) DEFAULT NULL,
  PRIMARY KEY (`PatientID`),
  KEY `idx_patients_nurse` (`NurseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `appointments` (
  `AppointmentID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) NOT NULL,
  `AppointmentDate` date NOT NULL,
  `AppointmentTime` time NOT NULL,
  `Reason` text DEFAULT NULL,
  `Status` varchar(50) NOT NULL DEFAULT 'Waiting',
  PRIMARY KEY (`AppointmentID`),
  KEY `idx_appointments_patient` (`PatientID`),
  KEY `idx_appointments_doctor_date` (`DoctorID`, `AppointmentDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `doctoravailability` (
  `DoctorAvailabilityID` int(11) NOT NULL AUTO_INCREMENT,
  `DoctorID` int(11) NOT NULL,
  `DayOfWeek` varchar(20) DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  PRIMARY KEY (`DoctorAvailabilityID`),
  KEY `idx_doctoravailability_doctor` (`DoctorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `doctorschedule` (
  `DoctorScheduleID` int(11) NOT NULL AUTO_INCREMENT,
  `DoctorID` int(11) NOT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `ScheduleDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `Status` varchar(50) NOT NULL DEFAULT 'Scheduled',
  `PatientName` varchar(200) DEFAULT NULL,
  `PatientAge` int(11) DEFAULT NULL,
  `FollowUpDate` datetime DEFAULT NULL,
  PRIMARY KEY (`DoctorScheduleID`),
  KEY `idx_doctorschedule_doctor_date` (`DoctorID`, `ScheduleDate`),
  KEY `idx_doctorschedule_patient` (`PatientID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `locations` (
  `LocationID` int(11) NOT NULL AUTO_INCREMENT,
  `RoomType` varchar(100) DEFAULT NULL,
  `RoomCapacity` int(11) NOT NULL DEFAULT 1,
  `Building` varchar(100) DEFAULT NULL,
  `Floor` varchar(50) DEFAULT NULL,
  `RoomNumber` varchar(50) DEFAULT NULL,
  `LocationName` varchar(200) NOT NULL,
  `Condition` varchar(100) DEFAULT NULL,
  `ConditionType` varchar(100) DEFAULT NULL,
  `RoomFee` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`LocationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `inpatients` (
  `InpatientID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `AdmissionDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DischargeDate` datetime DEFAULT NULL,
  `MedicalRecord` text DEFAULT NULL,
  `LocationID` int(11) DEFAULT NULL,
  `AssignedLocationID` int(11) DEFAULT NULL,
  PRIMARY KEY (`InpatientID`),
  KEY `idx_inpatients_patient` (`PatientID`),
  KEY `idx_inpatients_location` (`LocationID`),
  KEY `idx_inpatients_discharge` (`DischargeDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `outpatients` (
  `OutpatientID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `VisitDate` date DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  PRIMARY KEY (`OutpatientID`),
  KEY `idx_outpatients_patient` (`PatientID`),
  KEY `idx_outpatients_doctor` (`DoctorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `patientvitals` (
  `VitalID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) NOT NULL,
  `Temperature` varchar(30) DEFAULT NULL,
  `BloodPressure` varchar(30) DEFAULT NULL,
  `Pulse` varchar(30) DEFAULT NULL,
  `NurseNotes` text DEFAULT NULL,
  `NurseID` int(11) DEFAULT NULL,
  `AssignedNurseID` int(11) DEFAULT NULL,
  `RecordedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`VitalID`),
  KEY `idx_patientvitals_patient` (`PatientID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `emergency` (
  `EmergencyID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) DEFAULT NULL,
  `Symptoms` text DEFAULT NULL,
  `UrgencyLevel` varchar(50) DEFAULT NULL,
  `Name` varchar(200) DEFAULT NULL,
  `ContactNumber` varchar(30) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `LoggedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`EmergencyID`),
  KEY `idx_emergency_patient` (`PatientID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pharmacy` (
  `MedicineID` int(11) NOT NULL AUTO_INCREMENT,
  `MedicineName` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `StockQuantity` int(11) NOT NULL DEFAULT 0,
  `Price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `PharmacistID` int(11) DEFAULT NULL,
  PRIMARY KEY (`MedicineID`),
  KEY `idx_pharmacy_pharmacist` (`PharmacistID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `patientmedication` (
  `PatientMedicationID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) NOT NULL,
  `MedicineID` int(11) DEFAULT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `QuantityUsed` int(11) NOT NULL DEFAULT 1,
  `Status` varchar(50) NOT NULL DEFAULT 'Pending',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Dosage` varchar(100) DEFAULT NULL,
  `Frequency` varchar(100) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  PRIMARY KEY (`PatientMedicationID`),
  KEY `idx_patientmedication_patient` (`PatientID`),
  KEY `idx_patientmedication_doctor` (`DoctorID`),
  KEY `idx_patientmedication_medicine` (`MedicineID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `patientbilling` (
  `BillingID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `DoctorFee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `MedicineCost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `TotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `PaymentDate` datetime DEFAULT NULL,
  `Receipt` varchar(50) DEFAULT NULL,
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `CashierID` int(11) DEFAULT NULL,
  PRIMARY KEY (`BillingID`),
  KEY `idx_patientbilling_patient` (`PatientID`),
  KEY `idx_patientbilling_receipt` (`Receipt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `procedures` (
  `ProcedureID` int(11) NOT NULL AUTO_INCREMENT,
  `ProcedureName` varchar(200) NOT NULL,
  PRIMARY KEY (`ProcedureID`),
  UNIQUE KEY `uq_procedures_name` (`ProcedureName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `labprocedure` (
  `LabReqID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `TestDate` date DEFAULT NULL,
  `ProcedureName` varchar(200) NOT NULL,
  `Status` varchar(50) NOT NULL DEFAULT 'Pending',
  `Result` text DEFAULT NULL,
  `DateReleased` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`LabReqID`),
  KEY `idx_labprocedure_patient` (`PatientID`),
  KEY `idx_labprocedure_doctor` (`DoctorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(200) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`email`),
  UNIQUE KEY `uq_password_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
