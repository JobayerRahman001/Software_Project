-- ============================================
-- MEDILINK HEALTHCARE DATABASE - COMPLETE SQL
-- File: database/medilink.sql
-- ============================================

-- Create Database
DROP DATABASE IF EXISTS medilink;
CREATE DATABASE medilink CHARACTER SET utf8mb4;
USE medilink;

-- ============================================
-- TABLE 1: Users (for authentication)
-- ============================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(110) UNIQUE NOT NULL,
    password_hash VARCHAR(300) NOT NULL,
    role ENUM('patient', 'doctor', 'admin', 'staff', 'financial', 'datascientist', 'insurance') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- ============================================
-- TABLE 2: Patients
-- Connected to: patients CRUD (api/patients/)
-- ============================================
CREATE TABLE patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    phone VARCHAR(30),
    email VARCHAR(110),
    address TEXT,
    emergency_contact VARCHAR(30),
    blood_group VARCHAR(5),
    height DECIMAL(5,2) COMMENT 'in cm',
    weight DECIMAL(5,2) COMMENT 'in kg',
    bmi DECIMAL(4,2),
    allergies TEXT,
    chronic_conditions TEXT,
    insurance_provider VARCHAR(110),
    policy_number VARCHAR(60),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- TABLE 3: Doctors
-- Connected to: doctors CRUD (api/doctors/)
-- ============================================
CREATE TABLE doctors (
    doctor_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    specialization VARCHAR(120) NOT NULL,
    qualification TEXT,
    license_number VARCHAR(60) UNIQUE,
    phone VARCHAR(30),
    email VARCHAR(110),
    consultation_fee DECIMAL(10,2),
    available_days VARCHAR(60) COMMENT 'e.g., Mon-Fri',
    available_time VARCHAR(60) COMMENT 'e.g., 9AM-5PM',
    room_number VARCHAR(30),
    department VARCHAR(120),
    experience_years INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- TABLE 4: Medical Records
-- Connected to: medical_records CRUD (api/medical_records/)
-- ============================================
CREATE TABLE medical_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT,
    visit_date DATE NOT NULL,
    visit_type ENUM('Consultation', 'Follow-up', 'Emergency', 'Routine Checkup', 'Vaccination') DEFAULT 'Consultation',
    diagnosis TEXT,
    symptoms TEXT,
    treatment TEXT,
    medications TEXT,
    vital_signs TEXT COMMENT 'JSON format',
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    bmi DECIMAL(4,2),
    blood_pressure VARCHAR(30),
    temperature DECIMAL(4,2),
    heart_rate INT,
    notes TEXT,
    follow_up_date DATE,
    is_emergency BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL
);

-- ============================================
-- TABLE 5: Appointments
-- Connected to: appointments CRUD (api/appointments/)
-- ============================================
CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Scheduled', 'Confirmed', 'Cancelled', 'Completed', 'No-show') DEFAULT 'Scheduled',
    reason TEXT,
    duration_minutes INT DEFAULT 50,
    appointment_type ENUM('Consultation', 'Follow-up', 'Emergency', 'Test') DEFAULT 'Consultation',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_doctor_date (doctor_id, appointment_date)
);

-- ============================================
-- TABLE 6: Prescriptions
-- Connected to: prescriptions CRUD (api/prescriptions/)
-- ============================================
CREATE TABLE prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    prescription_date DATE NOT NULL,
    medicines TEXT NOT NULL,
    dosage TEXT,
    frequency VARCHAR(60),
    duration VARCHAR(60),
    route ENUM('Oral', 'Injection', 'Topical', 'Inhalation') DEFAULT 'Oral',
    instructions TEXT,
    refills_allowed INT DEFAULT 0,
    is_generic BOOLEAN DEFAULT FALSE,
    pharmacy_notes TEXT,
    diagnosis TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
);

-- ============================================
-- TABLE 7: Investigations
-- Connected to: investigations CRUD (api/investigations/)
-- ============================================
CREATE TABLE investigations (
    investigation_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT,
    test_name VARCHAR(120) NOT NULL,
    test_date DATE NOT NULL,
    test_type ENUM('Blood Test', 'X-Ray', 'MRI', 'CT Scan', 'Ultrasound', 'ECG', 'Urine Test') DEFAULT 'Blood Test',
    sample_type VARCHAR(60),
    lab_name VARCHAR(120),
    results TEXT,
    normal_range TEXT,
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    report_file VARCHAR(300),
    technician_notes TEXT,
    doctor_notes TEXT,
    is_urgent BOOLEAN DEFAULT FALSE,
    is_abnormal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL
);

-- ============================================
-- TABLE 8: Billing
-- Connected to: billing CRUD (api/billing/)
-- ============================================
CREATE TABLE billing (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    appointment_id INT,
    bill_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Paid', 'Partially Paid', 'Cancelled') DEFAULT 'Pending',
    payment_method VARCHAR(60),
    payment_date DATE,
    transaction_id VARCHAR(120),
    insurance_covered DECIMAL(10,2) DEFAULT 0,
    patient_paid DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);

-- ============================================
-- TABLE 9: Insurance Claims
-- Connected to: insurance_claims CRUD (api/insurance_claims/)
-- ============================================
CREATE TABLE insurance_claims (
    claim_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    bill_id INT NOT NULL,
    insurance_provider VARCHAR(120),
    policy_number VARCHAR(60),
    claim_amount DECIMAL(10,2) NOT NULL,
    claim_date DATE NOT NULL,
    status ENUM('Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid') DEFAULT 'Submitted',
    submission_date DATE,
    processed_date DATE,
    approved_amount DECIMAL(10,2),
    rejection_reason TEXT,
    documents TEXT COMMENT 'JSON array of document paths',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (bill_id) REFERENCES billing(bill_id) ON DELETE CASCADE,
    UNIQUE KEY unique_bill_claim (bill_id)
);

-- ============================================
-- TABLE 10: Symptoms Input
-- Connected to: symptoms_input CRUD (api/symptoms_input/)
-- ============================================
CREATE TABLE symptoms_input (
    symptom_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    symptoms TEXT NOT NULL,
    severity ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    duration_days INT,
    onset_date DATE,
    body_temperature DECIMAL(4,2),
    pain_level INT COMMENT '1-10 scale',
    affected_body_parts TEXT,
    other_symptoms TEXT,
    triggers TEXT,
    alleviating_factors TEXT,
    previous_treatments TEXT,
    allergies TEXT,
    notes TEXT,
    input_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- ============================================
-- TABLE 11: Disease Predictions
-- Connected to: disease_predictions CRUD (api/disease_predictions/)
-- ============================================
CREATE TABLE disease_predictions (
    prediction_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    symptom_id INT,
    predicted_disease VARCHAR(120) NOT NULL,
    confidence_score DECIMAL(5,2) NOT NULL COMMENT '0-100 percentage',
    suggestions TEXT,
    severity_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    recommended_tests TEXT,
    recommended_doctors TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    actual_diagnosis VARCHAR(120),
    verification_date DATE,
    verified_by_doctor_id INT,
    notes TEXT,
    prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (symptom_id) REFERENCES symptoms_input(symptom_id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by_doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL,
    INDEX idx_confidence (confidence_score),
    INDEX idx_prediction_date (prediction_date)
);

-- ============================================
-- TABLE 12: Recommendations
-- Connected to: recommendations CRUD (api/recommendations/)
-- ============================================
CREATE TABLE recommendations (
    recommendation_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    recommendation_type ENUM('Doctor', 'Test', 'Lifestyle', 'Medication', 'Checkup', 'Diet', 'Exercise') NOT NULL,
    recommendation_text TEXT NOT NULL,
    source ENUM('AI', 'Doctor', 'System') DEFAULT 'System',
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    category VARCHAR(50),
    implementation_timeframe VARCHAR(50),
    resources_needed TEXT,
    expected_benefits TEXT,
    risks TEXT,
    is_ai_generated BOOLEAN DEFAULT FALSE,
    confidence_score DECIMAL(5,2),
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    completion_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

-- ============================================
-- TABLE 13: Health Reminders
-- Connected to: health_reminders CRUD (api/health_reminders/)
-- ============================================
CREATE TABLE health_reminders (
    reminder_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    reminder_type VARCHAR(60) NOT NULL,
    reminder_text TEXT NOT NULL,
    reminder_date DATE NOT NULL,
    frequency ENUM('Once', 'Daily', 'Weekly', 'Monthly', 'Yearly') DEFAULT 'Once',
    is_recurring BOOLEAN DEFAULT FALSE,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    INDEX idx_reminder_date (reminder_date),
    INDEX idx_patient_status (patient_id, is_active, is_completed)
);

-- ============================================
-- TABLE 14: Staff (for front desk staff)
-- ============================================
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    department VARCHAR(120),
    phone VARCHAR(30),
    email VARCHAR(120),
    shift_timing VARCHAR(60),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- SAMPLE DATA INSERTION
-- ============================================

-- Insert sample users
INSERT INTO users (email, password_hash, role) VALUES 
('admin@medilink.com', 'admin.medilink@12345.', 'admin'),
('doctor@medilink.com', 'doctor.medilink@12345.', 'doctor'),
('patient@medilink.com', 'patient.medilink@12345.', 'patient'),
('staff@medilink.com', 'staff.medilink@12345.', 'staff'),
('financial@medilink.com', 'financial.medilink@12345.', 'financial'),
('datascientist@medilink.com', 'datascientist.medilink@12345.', 'datascientist'),
('insurance@medilink.com', 'insurance.medilink@12345.', 'insurance');

-- Insert sample patient
INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, phone, email) VALUES
(3, 'Jarin', 'Tasnim', '2005-05-15', 'Female', '+1234567890', 'jarin.tasnim@email.com');

-- Insert sample doctor
INSERT INTO doctors (user_id, first_name, last_name, specialization, phone, email) VALUES
(2, 'Kiron', 'Kodu', 'Cardiology', '+1234567891', 'kiron@medilink.com');

-- Insert sample staff
INSERT INTO staff (user_id, first_name, last_name, department, phone) VALUES
(4, 'Sarah', 'Hossain', 'Front Desk', '+1234567892');

-- Insert sample medical record
INSERT INTO medical_records (patient_id, doctor_id, visit_date, diagnosis, symptoms) VALUES
(1, 1, '2024-01-15', 'Hypertension', 'Headache, dizziness');

-- Insert sample appointment
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) VALUES
(1, 1, '2024-01-20', '10:00:00', 'Scheduled');

-- Insert sample prescription
INSERT INTO prescriptions (patient_id, doctor_id, prescription_date, medicines, dosage) VALUES
(1, 1, '2024-01-15', 'Lisinopril 10mg', 'Once daily');

-- Insert sample billing
INSERT INTO billing (patient_id, appointment_id, bill_date, total_amount, payment_status) VALUES
(1, 1, '2024-01-15', 1500.00, 'Pending');

-- Insert sample symptoms
INSERT INTO symptoms_input (patient_id, symptoms, severity, duration_days) VALUES
(1, 'Fever, Cough, Headache', 'Medium', 3);

-- Insert sample disease prediction
INSERT INTO disease_predictions (patient_id, symptom_id, predicted_disease, confidence_score) VALUES
(1, 1, 'Influenza', 85.5);

-- Insert sample recommendation
INSERT INTO recommendations (patient_id, recommendation_type, recommendation_text, priority) VALUES
(1, 'Lifestyle', 'Increase water intake and rest', 'Medium');

-- Insert sample health reminder
INSERT INTO health_reminders (patient_id, reminder_type, reminder_text, reminder_date) VALUES
(1, 'Medication', 'Take blood pressure medication', '2024-01-21');


-- ============================================
-- EXTRA DEMO DATA (dates are relative to today)
-- ============================================
INSERT INTO patients (first_name, last_name, date_of_birth, gender, phone, email, blood_group, insurance_provider) VALUES
('Maria','Alam','1992-03-21','Female','+1234567801','maria@email.com','O+','HealthFirst'),
('Ahmed','Khan','1975-11-02','Male','+1234567802','ahmed.khan@email.com','B+','CarePlus'),
('Lina','Hossain','2001-07-09','Female','+1234567803','lina@email.com','A-','HealthFirst'),
('Momtaz','Begum','1968-01-30','Male','+1234567804','momtaz@email.com','AB+','CarePlus');

INSERT INTO doctors (first_name, last_name, specialization, qualification, phone, email, consultation_fee, available_days, available_time, license_number, experience_years) VALUES
('Rafiq','Hasan','Neurology','MD, DM Neurology','+1234567811','rafiq.hasan@medilink.com',120,'Mon-Fri','9AM-5PM','LIC-1001',12),
('Kuddush','Turner','Pediatrics','MBBS, DCH','+1234567812','kuddush.turner@medilink.com',90,'Mon-Sat','10AM-4PM','LIC-1002',8),
('Omar','Ali','General Medicine','MBBS, MD','+1234567813','omar.ali@medilink.com',70,'Sun-Thu','8AM-2PM','LIC-1003',15);

INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, reason) VALUES
(2,1,CURDATE(),'09:30:00','Confirmed','Chest discomfort'),
(3,1,CURDATE(),'11:00:00','Scheduled','Regular checkup'),
(4,3,CURDATE(),'12:15:00','Scheduled','Fever and cough'),
(5,1,DATE_ADD(CURDATE(),INTERVAL 2 DAY),'10:00:00','Scheduled','Follow-up'),
(2,1,DATE_SUB(CURDATE(),INTERVAL 10 DAY),'14:00:00','Completed','Migraine'),
(3,4,DATE_SUB(CURDATE(),INTERVAL 35 DAY),'15:30:00','Completed','Allergy test');

INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, reason) VALUES
(1,1,DATE_ADD(CURDATE(),INTERVAL 3 DAY),'10:30:00','Scheduled','Blood pressure follow-up');
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, reason, appointment_type) VALUES
(4,3,CURDATE(),'13:00:00','Scheduled','Severe allergic reaction','Emergency');

INSERT INTO billing (patient_id, appointment_id, bill_date, total_amount, payment_status, payment_method, insurance_covered, patient_paid) VALUES
(2,6,DATE_SUB(CURDATE(),INTERVAL 10 DAY),900.00,'Paid','Card',500,400),
(3,7,DATE_SUB(CURDATE(),INTERVAL 35 DAY),450.00,'Paid','Cash',0,450),
(4,4,CURDATE(),700.00,'Pending',NULL,0,0),
(5,5,DATE_SUB(CURDATE(),INTERVAL 70 DAY),2100.00,'Partially Paid','Card',1000,600);

INSERT INTO insurance_claims (patient_id, bill_id, insurance_provider, claim_amount, claim_date, submission_date, status) VALUES
(2,2,'HealthFirst',500.00,DATE_SUB(CURDATE(),INTERVAL 9 DAY),DATE_SUB(CURDATE(),INTERVAL 9 DAY),'Approved'),
(5,5,'CarePlus',1000.00,DATE_SUB(CURDATE(),INTERVAL 60 DAY),DATE_SUB(CURDATE(),INTERVAL 60 DAY),'Under Review');

INSERT INTO prescriptions (patient_id, doctor_id, prescription_date, medicines, dosage, frequency, duration) VALUES
(2,1,CURDATE(),'Aspirin 75mg','1 tablet','Once daily','30 days'),
(4,3,CURDATE(),'Paracetamol 500mg','1 tablet','Every 8 hours','5 days'),
(3,3,DATE_SUB(CURDATE(),INTERVAL 5 DAY),'Cetirizine 10mg','1 tablet','At night','7 days');

INSERT INTO investigations (patient_id, doctor_id, test_name, test_date, test_type, results, status) VALUES
(2,1,'Lipid Profile',CURDATE(),'Blood Test','Pending lab result','Pending'),
(4,3,'Chest X-Ray',CURDATE(),'X-Ray','No abnormality detected','Completed'),
(5,1,'ECG',DATE_SUB(CURDATE(),INTERVAL 3 DAY),'ECG','Mild irregularity','Completed');

INSERT INTO disease_predictions (patient_id, predicted_disease, confidence_score, suggestions, severity_level) VALUES
(4,'Common Cold',65.0,'Rest and fluids','Low'),
(2,'Cardiac Issue',45.0,'See a cardiologist soon','High'),
(3,'Migraine',72.3,'Avoid triggers','Low'),
(5,'Influenza',85.5,'Rest, hydration','Medium');

INSERT INTO recommendations (patient_id, recommendation_type, recommendation_text, priority, source, is_ai_generated) VALUES
(2,'Checkup','Schedule a cardiology checkup within 2 weeks','High','AI',TRUE),
(4,'Diet','Reduce sugar and salt intake','Medium','Doctor',FALSE),
(3,'Exercise','30 minutes of walking daily','Low','System',FALSE);

INSERT INTO health_reminders (patient_id, reminder_type, reminder_text, reminder_date) VALUES
(2,'Medication','Take aspirin after breakfast',CURDATE()),
(4,'Checkup','Follow-up visit after fever',DATE_ADD(CURDATE(),INTERVAL 5 DAY));

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_patient_name ON patients(first_name, last_name);
CREATE INDEX idx_doctor_specialization ON doctors(specialization);
CREATE INDEX idx_appointment_status ON appointments(status);
CREATE INDEX idx_billing_status ON billing(payment_status);
CREATE INDEX idx_claims_status ON insurance_claims(status);

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- View: Patient Overview
CREATE VIEW patient_overview AS
SELECT p.patient_id, p.first_name, p.last_name, p.gender, p.date_of_birth,
       COUNT(DISTINCT a.appointment_id) as total_appointments,
       COUNT(DISTINCT mr.record_id) as total_visits,
       COUNT(DISTINCT pr.prescription_id) as total_prescriptions
FROM patients p
LEFT JOIN appointments a ON p.patient_id = a.patient_id
LEFT JOIN medical_records mr ON p.patient_id = mr.patient_id
LEFT JOIN prescriptions pr ON p.patient_id = pr.patient_id
GROUP BY p.patient_id;

-- View: Doctor Performance
CREATE VIEW doctor_performance AS
SELECT d.doctor_id, d.first_name, d.last_name, d.specialization,
       COUNT(DISTINCT a.appointment_id) as total_appointments,
       COUNT(DISTINCT pr.prescription_id) as total_prescriptions,
       AVG(dp.confidence_score) as avg_prediction_confidence
FROM doctors d
LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
LEFT JOIN prescriptions pr ON d.doctor_id = pr.doctor_id
LEFT JOIN disease_predictions dp ON d.doctor_id = dp.verified_by_doctor_id
GROUP BY d.doctor_id;

-- View: Billing Summary
CREATE VIEW billing_summary AS
SELECT 
    DATE_FORMAT(bill_date, '%Y-%m') as month,
    COUNT(*) as total_bills,
    SUM(total_amount) as total_revenue,
    SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END) as paid_amount,
    SUM(insurance_covered) as insurance_amount
FROM billing
GROUP BY DATE_FORMAT(bill_date, '%Y-%m')
ORDER BY month DESC;

-- ============================================
-- DATABASE COMPLETE
-- Total: 14 Tables, 2 Views, Sample Data
-- ============================================