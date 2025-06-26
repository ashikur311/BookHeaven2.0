<?php
require_once("../db_connection.php");
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize variables
$errors = [];
$success = "";

// Function to get user data
function getUserData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT u.*, ui.* FROM users u LEFT JOIN user_info ui ON u.user_id = ui.user_id WHERE u.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Bangladesh divisions for dropdown
$bangladesh_divisions = [
    'Dhaka', 'Chittagong', 'Rajshahi', 'Khulna', 'Barishal', 
    'Sylhet', 'Rangpur', 'Mymensingh'
];

// Function to update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user_name = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validate inputs
    if (empty($email)) {
        $_SESSION['error_message'] = "Email is required";
        header("Location: user_setting.php");
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format";
        header("Location: user_setting.php");
        exit();
    }
    
    // Handle file upload
    $profile_image_path = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/user_image/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error_message'] = "Failed to create upload directory";
                header("Location: user_setting.php");
                exit();
            }
        }
        
        $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        // Validate file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExt, $allowedExtensions)) {
            $_SESSION['error_message'] = "Invalid image file type. Only JPG, PNG, and GIF are allowed.";
            header("Location: user_setting.php");
            exit();
        }
        
        // Generate unique filename
        $fileName = 'user_' . $user_id . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
            // Store relative path in database
            $profile_image_path = 'assets/user_image/' . $fileName;
            
            // Delete old profile image if it exists and isn't the default
            $user = getUserData($conn, $user_id);
            if (!empty($user['user_profile']) && strpos($user['user_profile'], 'https://') === false) {
                $oldImagePath = '../' . $user['user_profile'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload profile image";
            header("Location: user_setting.php");
            exit();
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update users table
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $user_name, $email, $user_id);
        $stmt->execute();
        
        // Update profile image if uploaded
        if ($profile_image_path) {
            $stmt = $conn->prepare("UPDATE users SET user_profile = ? WHERE user_id = ?");
            $stmt->bind_param("si", $profile_image_path, $user_id);
            $stmt->execute();
        }
        
        // Check if user_info exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM user_info WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $count = $check_result->fetch_row()[0];
        
        if ($count > 0) {
            // Update existing user_info
            $stmt = $conn->prepare("UPDATE user_info SET phone = ?, birthday = ?, address = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $phone, $birthday, $address, $user_id);
        } else {
            // Insert new user_info
            $stmt = $conn->prepare("INSERT INTO user_info (user_id, phone, birthday, address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $phone, $birthday, $address);
        }
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: user_setting.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating profile: " . $e->getMessage();
        header("Location: user_setting.php");
        exit();
    }
}

// Handle profile image removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_profile_image'])) {
    $user = getUserData($conn, $user_id);
    
    if (!empty($user['user_profile']) && strpos($user['user_profile'], 'https://') === false) {
        $oldImagePath = '../' . $user['user_profile'];
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    }
    
    $default_image = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
    $stmt = $conn->prepare("UPDATE users SET user_profile = ? WHERE user_id = ?");
    $stmt->bind_param("si", $default_image, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile image removed successfully!";
    } else {
        $_SESSION['error_message'] = "Error removing profile image";
    }
    
    header("Location: user_setting.php");
    exit();
}

// Function to change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "All password fields are required";
        header("Location: user_setting.php");
        exit();
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match";
        header("Location: user_setting.php");
        exit();
    } elseif (strlen($new_password) < 8) {
        $_SESSION['error_message'] = "Password must be at least 8 characters long";
        header("Location: user_setting.php");
        exit();
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT pass FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($current_password, $user['pass'])) {
            $_SESSION['error_message'] = "Current password is incorrect";
            header("Location: user_setting.php");
            exit();
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET pass = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Password changed successfully!";
                header("Location: ../authentication.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Error changing password";
                header("Location: user_setting.php");
                exit();
            }
        }
    }
}

// Function to update billing address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_billing'])) {
    $street_address = $_POST['street_address'] ?? '';
    $city = $_POST['city'] ?? '';
    $division = $_POST['division'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';
    $country = 'Bangladesh'; // Fixed to Bangladesh
    
    // Validate inputs
    if (empty($street_address) || empty($city) || empty($division) || empty($zip_code)) {
        $_SESSION['error_message'] = "All required fields must be filled";
        header("Location: user_setting.php");
        exit();
    }
    
    // Check if billing address exists
    $check_stmt = $conn->prepare("SELECT id FROM user_billing_address WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing address
        $stmt = $conn->prepare("UPDATE user_billing_address SET 
            street_address = ?, 
            city = ?, 
            division = ?, 
            zip_code = ?, 
            country = ? 
            WHERE user_id = ?");
        $stmt->bind_param("sssssi", $street_address, $city, $division, $zip_code, $country, $user_id);
    } else {
        // Insert new address
        $stmt = $conn->prepare("INSERT INTO user_billing_address 
            (user_id, street_address, city, division, zip_code, country) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $street_address, $city, $division, $zip_code, $country);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Billing address updated successfully!";
        header("Location: user_setting.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating billing address: " . $conn->error;
        header("Location: user_setting.php");
        exit();
    }
}

// Function to handle payment methods
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $card_type = $_POST['card_type'] ?? 'visa';
    $card_number = $_POST['card_number'] ?? '';
    $card_name = $_POST['card_name'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Validate inputs
    if (empty($card_number) || empty($card_name) || empty($expiry_date) || empty($cvv)) {
        $_SESSION['error_message'] = "All payment fields are required";
        header("Location: user_setting.php");
        exit();
    } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $card_number))) {
        $_SESSION['error_message'] = "Invalid card number";
        header("Location: user_setting.php");
        exit();
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $_SESSION['error_message'] = "Invalid CVV";
        header("Location: user_setting.php");
        exit();
    }
    
    // Insert new payment method
    $stmt = $conn->prepare("INSERT INTO user_payment_methods (user_id, card_type, card_number, card_name, expiry_date, cvv) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $card_type, $card_number, $card_name, $expiry_date, $cvv);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Payment method added successfully!";
        header("Location: user_setting.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error adding payment method";
        header("Location: user_setting.php");
        exit();
    }
}

// Function to update two-step verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_verification'])) {
    $two_step_verification = isset($_POST['two_step_verification']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE users SET two_step_verification = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $two_step_verification, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Two-step verification settings updated!";
        header("Location: user_setting.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating verification settings";
        header("Location: user_setting.php");
        exit();
    }
}

// Get user data
$user = getUserData($conn, $user_id);

// Get billing address
$billing_address = [];
$stmt = $conn->prepare("SELECT * FROM user_billing_address WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $billing_address = $result->fetch_assoc();
}

// Get payment methods
$payment_methods = [];
$stmt = $conn->prepare("SELECT * FROM user_payment_methods WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_methods[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/user_setting.css">
    <style>
        .profile-image-upload {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .current-profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #ddd;
        }
        .image-upload-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
            border: none;
        }
        .btn-outline {
            background-color: transparent;
            border: 1px solid #ddd;
            color: #333;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
        .payment-method {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .payment-method-icon {
            font-size: 40px;
            margin-right: 15px;
            color: #555;
        }
        .payment-method-details {
            flex-grow: 1;
        }
        .payment-method-actions {
            display: flex;
            gap: 10px;
        }
        .verification-status {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .verification-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .form-check-input {
            margin-right: 10px;
        }
        .section-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-bottom: 3px solid transparent;
        }
        .tab-btn.active {
            border-bottom: 3px solid #4CAF50;
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include_once("../header.php"); ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <script>alert("<?php echo addslashes($_SESSION['error_message']); ?>");</script>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <script>alert("<?php echo addslashes($_SESSION['success_message']); ?>");</script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <main>
        <aside>
            <section class="user-info">
                <img src="/BookHeaven2.0/<?php echo htmlspecialchars($user['user_profile'] ?? 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                    alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                    <small>Member since: <?php echo date('M Y', strtotime($user['create_time'])); ?></small>
                </div>
            </section>
            <section>
                <nav>
                    <ul>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wish List</a></li>
                        <li><a href="user_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                        <li><a href="user_subscription.php"><i class="fas fa-calendar-check"></i> My Subscription</a></li>
                        <li><a href="user_setting.php" class="active"><i class="fas fa-cog"></i> Setting</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </section>
        </aside>
        <div class="settings_content">
            <div class="settings-container">
                <div class="settings-tabs">
                    <button class="tab-btn active" onclick="openTab(event, 'profile')">Profile Settings</button>
                    <button class="tab-btn" onclick="openTab(event, 'password')">Password</button>
                    <button class="tab-btn" onclick="openTab(event, 'billing')">Billing Address</button>
                    <button class="tab-btn" onclick="openTab(event, 'payment')">Payment Methods</button>
                    <button class="tab-btn" onclick="openTab(event, 'verification')">Two-Step Verification</button>
                </div>

                <!-- Profile Settings Tab -->
                <div id="profile" class="tab-content active">
                    <div class="section-header">
                        <h2>Profile Settings</h2>
                    </div>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="profile-image-upload">
                            <img src="/BookHeaven2.0/<?php echo htmlspecialchars($user['user_profile'] ?? 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                                alt="<?php echo htmlspecialchars($user['username']); ?>" class="current-profile-image" id="profileImagePreview">
                            <div class="image-upload-controls">
                                <div class="file-input-wrapper">
                                    <button type="button" class="btn btn-primary">Upload New Photo</button>
                                    <input type="file" id="profileImage" name="profile_image" accept="image/*" onchange="previewImage(this)">
                                </div>
                                <?php if (!empty($user['user_profile']) && strpos($user['user_profile'], 'https://') === false): ?>
                                    <button type="submit" name="remove_profile_image" class="btn btn-outline">Remove Photo</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="birthday">Date of Birth</label>
                            <input type="date" id="birthday" name="birthday" class="form-control" value="<?php echo htmlspecialchars($user['birthday'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Password Settings Tab -->
                <div id="password" class="tab-content">
                    <div class="section-header">
                        <h2>Change Password</h2>
                    </div>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>

                <!-- Billing Address Tab -->
                <div id="billing" class="tab-content">
                    <div class="section-header">
                        <h2>Billing Address</h2>
                    </div>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="streetAddress">Street Address</label>
                            <input type="text" id="streetAddress" name="street_address" class="form-control" 
                                   value="<?php echo htmlspecialchars($billing_address['street_address'] ?? ''); ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" class="form-control" 
                                       value="<?php echo htmlspecialchars($billing_address['city'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="division">Division</label>
                                <select id="division" name="division" class="form-control" required>
                                    <option value="">Select Division</option>
                                    <?php foreach ($bangladesh_divisions as $div): ?>
                                        <option value="<?php echo htmlspecialchars($div); ?>"
                                            <?php if (isset($billing_address['division']) && $billing_address['division'] === $div) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($div); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="zipCode">ZIP/Postal Code</label>
                                <input type="text" id="zipCode" name="zip_code" class="form-control" 
                                       value="<?php echo htmlspecialchars($billing_address['zip_code'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" class="form-control" 
                                       value="Bangladesh" readonly>
                            </div>
                        </div>
                        <button type="submit" name="update_billing" class="btn btn-primary">Update Address</button>
                    </form>
                </div>

                <!-- Payment Methods Tab -->
                <div id="payment" class="tab-content">
                    <div class="section-header">
                        <h2>Payment Methods</h2><br>
                    </div>
                    
                    <?php if (empty($payment_methods)): ?>
                        <p>No payment methods saved yet.</p><br><br>
                    <?php else: ?>
                        <?php foreach ($payment_methods as $method): ?>
                            <div class="payment-method">
                                <i class="fab fa-cc-<?php echo htmlspecialchars($method['card_type']); ?> payment-method-icon"></i>
                                <div class="payment-method-details">
                                    <h4><?php echo ucfirst(htmlspecialchars($method['card_type'])); ?> ending in <?php echo substr(htmlspecialchars($method['card_number']), -4); ?></h4>
                                    <p>Expires <?php echo htmlspecialchars($method['expiry_date']); ?></p>
                                </div>
                                <div class="payment-method-actions">
                                    <button class="btn btn-outline" onclick="openEditPaymentModal('<?php echo $method['id']; ?>')">Edit</button>
                                    <button class="btn btn-outline" onclick="confirmDeletePayment(<?php echo $method['id']; ?>)">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary" onclick="openAddPaymentModal()">Add Payment Method</button>
                </div>

                <!-- Two-Step Verification Tab -->
                <div id="verification" class="tab-content">
                    <div class="section-header">
                        <h2>Two-Step Verification</h2>
                    </div>
                    <div class="verification-status">
                        <h3>Status:</h3>
                        <span class="verification-badge <?php echo $user['two_step_verification'] ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $user['two_step_verification'] ? 'Enabled' : 'Not Enabled'; ?>
                        </span>
                    </div>
                    <p>Two-step verification adds an extra layer of security to your account by requiring more than just a password to log in.</p>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="two_step_verification" name="two_step_verification" <?php echo $user['two_step_verification'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="two_step_verification">Enable Two-Step Verification</label>
                            </div>
                        </div>
                        <button type="submit" name="update_verification" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include_once("../footer.php");?>

    <!-- Edit Payment Method Modal -->
    <div id="editPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Payment Method</h3>
                <button class="modal-close" onclick="closeModal('editPaymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editPaymentForm" method="POST" action="">
                    <input type="hidden" id="editPaymentId" name="payment_id">
                    <div class="form-group">
                        <label for="editCardType">Card Type</label>
                        <select id="editCardType" name="card_type" class="form-control">
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="amex">American Express</option>
                            <option value="discover">Discover</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editCardNumber">Card Number</label>
                        <input type="text" id="editCardNumber" name="card_number" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editExpDate">Expiration Date</label>
                            <input type="text" id="editExpDate" name="expiry_date" class="form-control" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label for="editCvv">CVV</label>
                            <input type="text" id="editCvv" name="cvv" class="form-control" placeholder="123" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editCardName">Name on Card</label>
                        <input type="text" id="editCardName" name="card_name" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button class="btn btn-primary" onclick="savePaymentChanges()">Save Changes</button>
                <button class="btn btn-outline" onclick="closeModal('editPaymentModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Add Payment Method Modal -->
    <div id="addPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Payment Method</h3>
                <button class="modal-close" onclick="closeModal('addPaymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addPaymentForm" method="POST" action="">
                    <div class="form-group">
                        <label for="cardType">Card Type</label>
                        <select id="cardType" name="card_type" class="form-control" required>
                            <option value="visa" selected>Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="amex">American Express</option>
                            <option value="discover">Discover</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cardNumber">Card Number</label>
                        <input type="text" id="cardNumber" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expDate">Expiration Date</label>
                            <input type="text" id="expDate" name="expiry_date" class="form-control" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cardName">Name on Card</label>
                        <input type="text" id="cardName" name="card_name" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" name="add_payment" class="btn btn-primary">Add Payment Method</button>
                        <button type="button" class="btn btn-outline" onclick="closeModal('addPaymentModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            // Remove active class from all tab buttons
            const tabButtons = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }

            // Show the current tab and add active class to the button
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('profileImagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
                
                // Ensure the form has the correct enctype
                const form = input.closest('form');
                if (form) {
                    form.setAttribute('enctype', 'multipart/form-data');
                }
            }
        }

        // Payment method modals
        function openEditPaymentModal(paymentId) {
            document.getElementById('editPaymentId').value = paymentId;
            document.getElementById('editPaymentModal').style.display = 'flex';
        }

        function openAddPaymentModal() {
            document.getElementById('addPaymentModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function savePaymentChanges() {
            const form = document.getElementById('editPaymentForm');
            const formData = new FormData(form);
           
            fetch('update_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment method updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating payment method: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating payment method');
            });
        }

        function confirmDeletePayment(paymentId) {
            if (confirm('Are you sure you want to delete this payment method?')) {
                fetch(`delete_payment.php?id=${paymentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment method deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting payment method: ' + data.message);
                    }
                });
            }
        }
        
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // Format card number input
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '');
            if (value.length > 0) {
                value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
            }
            e.target.value = value;
        });
        
        // Format expiry date input
        document.getElementById('expDate').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Initialize the first tab as active
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab-btn.active').click();
        });
    </script>
</body>
</html>