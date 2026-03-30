<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Get user's orders
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Orders - Online Canteen System</title>
  <link rel="stylesheet" href="css/menu.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  
  <div class="menu-container">
    <h1>My Orders</h1>
    
    <?php if(mysqli_num_rows($result) > 0): ?>
      <div class="orders-list">
        <?php while($order = mysqli_fetch_assoc($result)): ?>
          <div class="order-card">
            <div class="order-header">
              <h3>Order #<?php echo $order['id']; ?></h3>
              <span class="order-status <?php echo $order['status']; ?>">
                <?php echo ucfirst($order['status']); ?>
              </span>
            </div>
            <div class="order-details">
              <p><strong>Total:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
              <p><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
              <?php if($order['payment_method']): ?>
                <p><strong>Payment:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
              <?php endif; ?>
              
              <?php if($order['seat_number']): ?>
                <p><strong>🪑 Seat Number:</strong> <span style="background: #001f3f; color: white; padding: 3px 8px; border-radius: 4px; font-weight: bold;"><?php echo $order['seat_number']; ?></span></p>
              <?php endif; ?>
              
              <?php 
              // Get order items
              $items_query = "SELECT oi.*, d.name FROM order_items oi 
                             JOIN dishes d ON oi.dish_id = d.id 
                             WHERE oi.order_id = ?";
              $items_stmt = mysqli_prepare($connection, $items_query);
              mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
              mysqli_stmt_execute($items_stmt);
              $items_result = mysqli_stmt_get_result($items_stmt);
              
              if(mysqli_num_rows($items_result) > 0):
              ?>
                <div class="order-items">
                  <strong>Items:</strong>
                  <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                    <div style="margin: 5px 0; padding-left: 20px;">
                      <?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?> - ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                  <?php endwhile; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div style="text-align: center; padding: 50px; margin-bottom: 340px;">
        <h2>No orders yet</h2>
        <p><a href="menu.php">Browse our menu and place your first order!</a></p>
      </div>
    <?php endif; ?>
  </div>

  <?php include 'includes/footer.php'; ?>
</body>
</html>
