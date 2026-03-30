<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Handle POST requests
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['remove_item'])){
        $dish_id = $_POST['remove_item'];
        $query = "DELETE FROM cart_items WHERE user_id = ? AND dish_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $dish_id);
        mysqli_stmt_execute($stmt);
        header("Location: cart.php");
        exit();
    }
    if(isset($_POST['update_quantity'])){
        $dish_id = $_POST['update_quantity'];
        $qty = (int)$_POST['quantity'];
        if($qty > 0){
            $query = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND dish_id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "iii", $qty, $_SESSION['user_id'], $dish_id);
            mysqli_stmt_execute($stmt);
        } else {
            $query = "DELETE FROM cart_items WHERE user_id = ? AND dish_id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $dish_id);
            mysqli_stmt_execute($stmt);
        }
        header("Location: cart.php");
        exit();
    }
    if(isset($_POST['clear_cart'])){
        $query = "DELETE FROM cart_items WHERE user_id = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        header("Location: cart.php");
        exit();
    }
    if(isset($_POST['place_order'])){
        // Redirect to payment page
        header("Location: payment.php");
        exit();
    }
}

// Get cart items from database
$query = "SELECT ci.*, d.name, d.price FROM cart_items ci 
         JOIN dishes d ON ci.dish_id = d.id 
         WHERE ci.user_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cart_items = array();
$total_amount = 0;
$total_items = 0;

while($item = mysqli_fetch_assoc($result)){
    $cart_items[] = $item;
    $total_amount += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart - Online Canteen System</title>
  <link rel="stylesheet" href="css/cart.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  
  <div class="cart-container">
    <h1>Shopping Cart</h1>
    
    
    <?php if(empty($cart_items)): ?>
      <div class="empty-cart">
        <div class="empty-cart-icon">🛒</div>
        <h2>Your cart is empty</h2>
        <p>Looks like you haven't added any items to your cart yet.</p>
        <a href="menu.php" class="btn-primary">Browse Menu</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <!-- Cart Items -->
        <div class="cart-items">
          <div class="cart-header">
            <h3>Items (<?php echo $total_items; ?>)</h3>
            <form method="POST" action="cart.php" class="clear-cart-form">
              <input type="hidden" name="clear_cart" value="1">
              <button type="submit" class="btn-clear" onclick="return confirm('Clear cart?')">Clear Cart</button>
            </form>
          </div>
          
          <?php foreach($cart_items as $item): ?>
            <div class="cart-item">
              <div class="item-info">
                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                <p class="item-price">₹<?php echo number_format($item['price'], 2); ?> per item</p>
              </div>
              
              <div class="item-controls">
                <form method="POST" action="cart.php" class="quantity-form">
                  <input type="hidden" name="update_quantity" value="<?php echo $item['dish_id']; ?>">
                  <div class="quantity-controls">
                    <button type="button" class="qty-btn" onclick="this.form.quantity.value=parseInt(this.form.quantity.value)-1; this.form.submit()">-</button>
                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="qty-input">
                    <button type="button" class="qty-btn" onclick="this.form.quantity.value=parseInt(this.form.quantity.value)+1; this.form.submit()">+</button>
                  </div>
                </form>
                
                <div class="item-total">
                  ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </div>
                
                <form method="POST" action="cart.php" class="remove-form">
                  <input type="hidden" name="remove_item" value="<?php echo $item['dish_id']; ?>">
                  <button type="submit" class="btn-remove" onclick="return confirm('Remove item?')">✕</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
          <h3>Order Summary</h3>
          
          <div class="summary-row">
            <span>Subtotal (<?php echo $total_items; ?> items)</span>
            <span>₹<?php echo number_format($total_amount, 2); ?></span>
          </div>
          
          <div class="summary-row total">
            <span>Total</span>
            <span>₹<?php echo number_format($total_amount, 2); ?></span>
          </div>
          
          <div class="checkout-section">
            <form method="POST" action="cart.php" class="checkout-form">
              <input type="hidden" name="place_order" value="1">
              <button type="submit" class="btn-checkout">Place Order</button>
            </form>
            
            <a href="menu.php" class="continue-shopping">Continue Shopping</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php include 'includes/footer.php'; ?>
</body>
</html>
