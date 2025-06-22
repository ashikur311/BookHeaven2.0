<?php
// Include your database connection file
// i // Adjust this to your actual connection file
include_once("../db_connection.php");
// Function to generate random dates
function randomDate($startDate, $endDate) {
    $min = strtotime($startDate);
    $max = strtotime($endDate);
    $val = rand($min, $max);
    return date('Y-m-d', $val);
}

// Function to generate random invoice numbers
function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(md5(microtime()), 0, 6));
}

// Array of sample first names
$firstNames = ['James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph', 'Thomas', 'Daniel', 
              'Emma', 'Olivia', 'Ava', 'Isabella', 'Sophia', 'Charlotte', 'Mia', 'Amelia', 'Harper', 'Evelyn'];

// Array of sample last names
$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
             'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];

// Array of sample street names
$streets = ['Main St', 'Oak Ave', 'Pine Rd', 'Maple Dr', 'Elm St', 'Cedar Ln', 'Birch Way', 'Willow Blvd', 'Spruce Ct', 'Magnolia Ave'];

// Array of sample cities
$cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose'];

// Plan details - assuming these amounts for demonstration
$plans = [
    1 => ['amount' => 9.99, 'duration' => '+1 month'],
    2 => ['amount' => 24.99, 'duration' => '+3 months'],
    3 => ['amount' => 89.99, 'duration' => '+1 year']
];

// Generate and insert 50 users
for ($i = 1; $i <= 50; $i++) {
    // Generate random user data
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    $username = strtolower($firstName[0] . $lastName . rand(1, 99));
    $email = strtolower($firstName . '.' . $lastName . rand(1, 99) . '@example.com');
    $password = 'password' . $i; // Simple password for testing
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Generate address
    $streetNum = rand(100, 9999);
    $street = $streets[array_rand($streets)];
    $city = $cities[array_rand($cities)];
    $state = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2);
    $zip = rand(10000, 99999);
    $address = "$streetNum $street, $city, $state $zip";
    
    // Generate date of birth (between 18 and 80 years ago)
    $dob = randomDate('-80 years', '-18 years');
    
    // Generate phone number
    $contact = rand(200, 999) . '-' . rand(200, 999) . '-' . rand(1000, 9999);
    
    try {
        // Check if email exists (unlikely with our generation but good practice)
        $checkEmail = $conn->query("SELECT * FROM users WHERE email = '$email'");
        if ($checkEmail->num_rows > 0) {
            continue; // Skip if email exists (shouldn't happen with our generation)
        }
        
        // Insert into users table
        $conn->query("INSERT INTO users (username, email, pass) VALUES ('$username', '$email', '$hashedPassword')");
        $user_id = $conn->insert_id;
        
        // Insert into user_info table
        $conn->query("INSERT INTO user_info (user_id, birthday, phone, address) VALUES ($user_id, '$dob', '$contact', '$address')");
        
        // Now create a subscription order for this user
        $plan_id = rand(1, 3); // Randomly select plan 1, 2, or 3
        $plan = $plans[$plan_id];
        $amount = $plan['amount'];
        $invoice_number = generateInvoiceNumber();
        $issue_date = date('Y-m-d');
        $expire_date = date('Y-m-d', strtotime($plan['duration']));
        
        // Random payment status (80% paid, 20% unpaid)
        $payment_status = (rand(1, 100) <= 80 ? 'paid' : 'unpaid');
        
        // Random payment method if paid
        $payment_method = $payment_status == 'paid' ? 
            ['credit_card', 'paypal', 'bank_transfer'][rand(0, 2)] : NULL;
        
        // Insert subscription order
        $conn->query("INSERT INTO subscription_orders 
                     (user_id, plan_id, amount, invoice_number, status, payment_status, issue_date, expire_date, payment_method) 
                     VALUES 
                     ($user_id, $plan_id, $amount, '$invoice_number', 'active', '$payment_status', '$issue_date', '$expire_date', " . 
                     ($payment_method ? "'$payment_method'" : "NULL") . ")");
        
        echo "User $i created: $username ($email) with plan $plan_id<br>";
    } catch (Exception $e) {
        echo "Error creating user $i: " . $e->getMessage() . "<br>";
    }
}

echo "Completed inserting 50 users with subscription orders.";
?>