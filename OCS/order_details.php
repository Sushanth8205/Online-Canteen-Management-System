<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;
if(!$order_id){
    header("Location: admin.php?page=orders");
    exit();
}

// Get order details with user information
$order_query = "SELECT o.*, u.username, u.full_name, u.email, u.phone, u.role_of 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
$order_stmt = mysqli_prepare($connection, $order_query);
mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

if(mysqli_num_rows($order_result) == 0){
    header("Location: admin.php?page=orders");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, d.name, d.description 
                FROM order_items oi 
                JOIN dishes d ON oi.dish_id = d.id 
                WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($connection, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Details #<?php echo $order['id']; ?> - Admin Panel</title>
  <link rel="stylesheet" href="css/order_details.css">
</head>
<body>
  <div class="main-content">
    <div class="order-details-container">
    <div class="order-header">
      <h2>Order #<?php echo $order['id']; ?></h2>
      <span class="order-status status-<?php echo $order['status']; ?>">
        <?php echo ucfirst($order['status']); ?>
      </span>
    </div>
    
    <!-- Customer Information -->
    <div class="section">
      <h3>👤 Customer Information</h3>
      <div class="info-grid">
        <div class="info-item">
          <strong>Name</strong>
          <?php echo htmlspecialchars($order['full_name']); ?>
        </div>
        <div class="info-item">
          <strong>Username</strong>
          <?php echo htmlspecialchars($order['username']); ?>
        </div>
        <div class="info-item">
          <strong>Email</strong>
          <?php echo htmlspecialchars($order['email']); ?>
        </div>
        <div class="info-item">
          <strong>Phone</strong>
          <?php echo htmlspecialchars($order['phone']); ?>
        </div>
        <div class="info-item">
          <strong>Role</strong>
          <span class="role-badge role-<?php echo $order['role_of']; ?>">
            <?php echo ucfirst($order['role_of']); ?>
          </span>
        </div>
        <div class="info-item">
          <strong>Seat Number</strong>
          <?php if($order['seat_number']): ?>
            <span class="seat-badge">🪑 <?php echo $order['seat_number']; ?></span>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Order Information -->
    <div class="section">
      <h3>📋 Order Information</h3>
      <div class="info-grid">
        <div class="info-item">
          <strong>Order Date</strong>
          <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
        </div>
        <div class="info-item">
          <strong>Payment Method</strong>
          <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?>
        </div>
      </div>
    </div>
    
    <!-- Order Items -->
    <div class="section">
      <h3>🍽️ Order Items</h3>
      <table class="items-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Description</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $subtotal = 0;
          while($item = mysqli_fetch_assoc($items_result)): 
            $item_total = $item['price'] * $item['quantity'];
            $subtotal += $item_total;
          ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
              <td><?php echo htmlspecialchars($item['description'] ?? '-'); ?></td>
              <td><?php echo $item['quantity']; ?></td>
              <td>₹<?php echo number_format($item['price'], 2); ?></td>
              <td>₹<?php echo number_format($item_total, 2); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      
      <div class="total-section">
        <div>Subtotal: ₹<?php echo number_format($subtotal, 2); ?></div>
        <div class="total-amount">Total Amount: ₹<?php echo number_format($order['total_amount'], 2); ?></div>
      </div>
    </div>
    
    <button class="close-btn" onclick="window.close()">Close</button>
    </div>
    <link rel="stylesheet" href="css/footer.css">
    <footer>
        &copy; 2025 Online Canteen System | All Rights Reserved
    </footer>
</body>
</html>
