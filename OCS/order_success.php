<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['order_success'])){
    header("Location: home.php");
    exit();
}

unset($_SESSION['order_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Successful - Online Canteen System</title>
  <link rel="stylesheet" href="css/payment.css">
  <link rel="stylesheet" href="css/order_success.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  
  <div class="payment-container">
    <div class="success-message">
      <div class="success-icon">✅</div>
      <h1>Order Placed Successfully!</h1>
      <p>Thank you for your order. We'll prepare your food and deliver it soon.</p>
      
      <div class="order-info">
        <h3>What's Next?</h3>
        <ul>
          <li>📱 You'll receive order updates</li>
          <li>🍳 Your order is being prepared</li>
        </ul>
      </div>
      
      <div class="action-buttons">
        <a href="orders.php" class="btn-primary">View My Orders</a>
        <a href="menu.php" class="btn-secondary">Order More Food</a>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</body>
</html>
