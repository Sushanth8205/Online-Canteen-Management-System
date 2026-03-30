<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Online Canteen System</title>
  <link rel="stylesheet" href="css/header.css">
</head>
<body>
  <header>
    <h1>Online Canteen System</h1>
    <nav>
      <a href="home.php">Home</a>
      <a href="menu.php">Menu</a>
      
      <?php if(isset($_SESSION['user_id'])): ?>
        <?php if($_SESSION['user_role'] == 'admin'): ?>
          <a href="admin.php">Admin Panel</a>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="cart.php"> Cart</a>
          <a href="orders.php">My Orders</a>
          <a href="logout.php">Logout</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="signup.php">Signup</a>
      <?php endif; ?>
    </nav>
  </header>
