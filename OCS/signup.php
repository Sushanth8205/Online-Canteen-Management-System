<?php
require_once 'db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Get form data
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $role_of = $_POST['role_of'];
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if email or username already exists
    $check_query = "SELECT id FROM users WHERE email = ? OR username = ?";
    $check_stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ss", $email, $username);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($check_result) > 0){
        echo "<script>alert('Email or username already exists!'); window.location.href='signup.php';</script>";
        exit();
    }
    
    // Insert user
    $insert_query = "INSERT INTO users (full_name, email, username, phone, password_hash, role_of) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($connection, $insert_query);
    
    mysqli_stmt_bind_param($stmt, "ssssss", $fullname, $email, $username, $phone, $password_hash, $role_of);
    
    if(mysqli_stmt_execute($stmt)){
        echo "<script>
            alert('Account created successfully!');
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 1000);
        </script>";
    } else {
        echo "Registration failed. Error: " . mysqli_error($connection);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Signup - Online Canteen System</title>
  <link rel="stylesheet" href="css/signup.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  
  <main>
    <div class="signup-box">
      <h2>Create an Account</h2>
      
      <form method="POST" action="signup.php">
        <div class="form-row">
          <label for="fullname">Full name</label>
          <input id="fullname" name="fullname" type="text" 
                 placeholder="e.g. Srujan S Shetty" required>
        </div>

        <div class="form-row">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" 
                 placeholder="you@example.com" required>
        </div>

        <div class="form-row">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" 
                 placeholder="username" required>
        </div>

        <div class="form-row">
          <label for="phone">Phone</label>
          <input id="phone" name="phone" type="tel" 
                 placeholder="10 digit phone" required>
        </div>

        <div class="form-row">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" 
                 minlength="6" placeholder="Enter password" required>
        </div>

        <div class="form-row">
          <label for="role">User Type</label>
          <select id="role" name="role_of" required>
            <option value="">Select user type</option>
            <option value="customer">Customer</option>
            <option value="staff">Staff</option>
          </select>
        </div>

        <div class="actions">
          <button type="submit">Sign Up</button>
        </div>
      </form>

      <div class="note">
        <p>Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>
</body>
</html>
