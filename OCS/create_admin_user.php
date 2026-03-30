<?php
require_once 'db.php';

// Admin credentials
$username = 'admin';
$password = 'admin123';
$full_name = 'Administrator';
$email = 'admin@canteen.com';
$phone = '1234567890';
$admin_level = 'super_admin';

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin user already exists in admins table
$check_query = "SELECT id FROM admins WHERE username = ? OR email = ?";
$check_stmt = mysqli_prepare($connection, $check_query);
mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if(mysqli_num_rows($result) > 0) {
    echo "Admin user already exists!<br>";
    
    // Update the existing admin
    $update_query = "UPDATE admins SET password_hash = ?, full_name = ?, phone = ?, admin_level = ? WHERE username = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "sssss", $password_hash, $full_name, $phone, $admin_level, $username);
    
    if(mysqli_stmt_execute($update_stmt)) {
        echo "Admin user updated successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "Admin Level: " . ucwords(str_replace('_', ' ', $admin_level)) . "<br>";
    } else {
        echo "Error updating admin user: " . mysqli_error($connection) . "<br>";
    }
} else {
    // Insert new admin directly into admins table
    $insert_query = "INSERT INTO admins (full_name, email, username, phone, password_hash, admin_level, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
    $insert_stmt = mysqli_prepare($connection, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "ssssss", $full_name, $email, $username, $phone, $password_hash, $admin_level);
    
    if(mysqli_stmt_execute($insert_stmt)) {
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "Admin Level: " . ucwords(str_replace('_', ' ', $admin_level)) . "<br>";
        echo "Admin ID: " . mysqli_insert_id($connection) . "<br>";
    } else {
        echo "Error creating admin user: " . mysqli_error($connection) . "<br>";
    }
}

echo "<br><a href='login.php'>Go to Login Page</a>";
?>
