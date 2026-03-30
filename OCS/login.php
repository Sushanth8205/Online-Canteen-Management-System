<?php
session_start();
require_once 'db.php';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    //get username and password from form
    $username = $_POST['username'];
    $password = $_POST['password'];

    //check if user exists in users table - using prepared statement for security
    $query = "SELECT id, full_name, email, username, password_hash, role_of FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0){
        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password_hash'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role_of'];

            header("Location: home.php");
            exit();
        }else{
            echo "<script>alert('Wrong password! Please try again.'); window.location.href='login.php';</script>";
        }
    }else{
        // Check if admin exists in admins table
        $admin_query = "SELECT id, full_name, email, username, password_hash, admin_level FROM admins WHERE username = ? OR email = ?";
        $admin_stmt = mysqli_prepare($connection, $admin_query);
        mysqli_stmt_bind_param($admin_stmt, "ss", $username, $username);
        mysqli_stmt_execute($admin_stmt);
        $admin_result = mysqli_stmt_get_result($admin_stmt);

        if(mysqli_num_rows($admin_result) > 0){
            $admin = mysqli_fetch_assoc($admin_result);

            if(password_verify($password, $admin['password_hash'])){
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['admin_level'] = $admin['admin_level'];

                header("Location: admin.php");
                exit();
            }else{
                echo "Wrong password";
            }
        }else{
            echo "<script>alert('User not found! Please check your credentials.'); window.location.href='login.php';</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Online Canteen System</title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  <div class="login-box">
    <h2>Login</h2>
    <form action="login.php" method="POST">
      <input type="text" id="username" name="username" placeholder="Username or Email" required>
      <input type="password" id="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>

    <div class="signup-link">
      <p>Don't have an account? <a href="signup.php">Signup here</a></p>
    </div>
  </div>
  <div class="empty">

  </div>
  <?php include 'includes/footer.php'; ?>
</body>
</html>