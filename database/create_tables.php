<?php

include "../config/db.php";

$tables = <<<SQL
-- 1. INSTITUTIONS table (Colleges and Hospitals with admin login)
CREATE TABLE institutions (
  institution_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('college', 'hospital') NOT NULL,
  address TEXT NULL,
  latitude DECIMAL(10,8) NULL,
  longitude DECIMAL(11,8) NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  email VARCHAR(100) NOT NULL UNIQUE,  -- login for admin
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. USERS table (Only students and general users)
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(15),
  role ENUM('user', 'student') NOT NULL,
  institution_id INT NULL,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  status ENUM('active', 'inactive') DEFAULT 'active',
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (institution_id) REFERENCES institutions(institution_id)
);


-- 3. DONORS table
CREATE TABLE donors (
  donor_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  blood_group ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-') NOT NULL,
  is_available BOOLEAN DEFAULT TRUE,
  is_confirmed BOOLEAN DEFAULT FALSE,
  last_donated DATE,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 4. EVENTS table (by college admin)
CREATE TABLE events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  institution_id INT,
  title VARCHAR(100),
  description TEXT,
  date DATE,
  location VARCHAR(255),
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  FOREIGN KEY (institution_id) REFERENCES institutions(institution_id)
);

-- 5. EVENT PARTICIPATION table
CREATE TABLE event_participation (
  participation_id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT,
  user_id INT,
  attended BOOLEAN DEFAULT FALSE,
  donated BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (event_id) REFERENCES events(event_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 6. EMERGENCY REQUESTS by hospital admin
CREATE TABLE emergency_requests (
  request_id INT AUTO_INCREMENT PRIMARY KEY,
  institution_id INT,
  blood_group ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'),
  message TEXT,
  latitude DECIMAL(10,8),
  longitude DECIMAL(11,8),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (institution_id) REFERENCES institutions(institution_id)
);

-- 7. REQUEST RESPONSES by users
CREATE TABLE request_responses (
  response_id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT,
  user_id INT,
  status ENUM('accepted', 'rejected') DEFAULT 'accepted',
  responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES emergency_requests(request_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 8. DONATIONS history (verified)
CREATE TABLE donations (
  donation_id INT AUTO_INCREMENT PRIMARY KEY,
  donor_id INT,
  event_id INT NULL,
  date DATE,
  location VARCHAR(255),
  verified_by INT,  -- institution_id
  FOREIGN KEY (donor_id) REFERENCES donors(donor_id),
  FOREIGN KEY (event_id) REFERENCES events(event_id),
  FOREIGN KEY (verified_by) REFERENCES institutions(institution_id)
);

-- 9. MAIN ADMIN login table (optional system-level admin)
CREATE TABLE main_admin (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);


SQL;

if($conn->multi_query($tables)){
    do{
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }while($conn->next_result());
    echo "All tables are created successfully";

}else{
    echo "Error creating tables ".$conn->connect_error;
}

?>