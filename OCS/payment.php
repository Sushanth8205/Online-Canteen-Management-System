<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Get cart items from database
$query = "SELECT ci.*, d.name, d.price FROM cart_items ci 
         JOIN dishes d ON ci.dish_id = d.id 
         WHERE ci.user_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0){
    header("Location: cart.php");
    exit();
}

$cart_items = array();
$total_amount = 0;
$total_items = 0;

while($item = mysqli_fetch_assoc($result)){
    $cart_items[] = $item;
    $total_amount += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

// Handle payment processing
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Debug: Check if form is submitting
    error_log("POST request received: " . print_r($_POST, true));
    
    if(isset($_POST['process_payment'])){
        $payment_method = $_POST['payment_method'];
        $upi_id = $_POST['upi_id'] ?? '';
        $card_number = $_POST['card_number'] ?? '';
        
        // Validate payment details
        if($payment_method == 'upi' && empty($upi_id)){
            $error = "Please enter your UPI ID";
        } elseif($payment_method == 'card' && empty($card_number)){
            $error = "Please enter your card details";
        } else {
            // Check if user is staff
            $user_query = "SELECT role_of FROM users WHERE id = ?";
            $user_stmt = mysqli_prepare($connection, $user_query);
            mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($user_stmt);
            $user_result = mysqli_stmt_get_result($user_stmt);
            $user = mysqli_fetch_assoc($user_result);
            
            $seat_number = null;
            if($user && $user['role_of'] == 'staff') {
                // Generate random seat number for staff users
                $seat_number = rand(1, 50);
            }
            
            // Create order
            $order_query = "INSERT INTO orders (user_id, total_amount, status, payment_method, seat_number) VALUES (?, ?, 'confirmed', ?, ?)";
            $order_stmt = mysqli_prepare($connection, $order_query);
            mysqli_stmt_bind_param($order_stmt, "idsi", $_SESSION['user_id'], $total_amount, $payment_method, $seat_number);
            mysqli_stmt_execute($order_stmt);
            
            // Get order ID
            $order_id = mysqli_insert_id($connection);
            
            // Insert order items
            foreach($cart_items as $item){
                $item_query = "INSERT INTO order_items (order_id, dish_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = mysqli_prepare($connection, $item_query);
                mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $item['dish_id'], $item['quantity'], $item['price']);
                mysqli_stmt_execute($item_stmt);
            }
            
            // Clear cart
            $clear_query = "DELETE FROM cart_items WHERE user_id = ?";
            $clear_stmt = mysqli_prepare($connection, $clear_query);
            mysqli_stmt_bind_param($clear_stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($clear_stmt);
            
            $_SESSION['order_success'] = true;
            header("Location: order_success.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment - Online Canteen System</title>
  <link rel="stylesheet" href="css/payment.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  
  <div class="payment-container">
    <h1>Complete Your Payment</h1>
    
    <?php if(isset($error)): ?>
      <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <!-- Order Summary -->
    <div class="order-summary">
      <h2>Order Summary</h2>
      <?php foreach($cart_items as $item): ?>
        <div class="summary-item">
          <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
          <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
        </div>
      <?php endforeach; ?>
      
      <div class="summary-total">
        <span>Total Amount:</span>
        <span>₹<?php echo number_format($total_amount, 2); ?></span>
      </div>
    </div>
    
    <!-- Payment Form -->
    <div class="payment-form">
      <h2>Payment Method</h2>
      <form method="POST" action="payment.php" onsubmit="console.log('Form submitting!'); return true;">
        <input type="hidden" name="process_payment" value="1">
        
        <div class="payment-methods">
          <label class="payment-option">
            <input type="radio" name="payment_method" value="upi" checked>
            <div class="payment-card">
              <div class="payment-icon">📱</div>
              <div class="payment-info">
                <h3>UPI Payment</h3>
                <p>Pay using UPI apps</p>
              </div>
            </div>
          </label>
          
          <label class="payment-option">
            <input type="radio" name="payment_method" value="card">
            <div class="payment-card">
              <div class="payment-icon">💳</div>
              <div class="payment-info">
                <h3>Credit/Debit Card</h3>
                <p>Pay using your card</p>
              </div>
            </div>
          </label>
        </div>
        
        <!-- UPI Payment Details -->
        <div class="payment-details" id="upi-details">
          <h3>UPI Details</h3>
          <input type="text" name="upi_id" placeholder="Enter your UPI ID (e.g., user@paytm)">
        </div>
        
        <!-- Card Payment Details -->
        <div class="payment-details" id="card-details" style="display: none;">
          <h3>Card Details</h3>
          <input type="text" name="card_number" placeholder="Card Number" maxlength="16">
          <div class="card-row">
            <input type="text" name="expiry" placeholder="MM/YY" maxlength="5">
            <input type="text" name="cvv" placeholder="CVV" maxlength="3">
          </div>
        </div>
        
        
        <button type="submit" class="pay-button">Complete Payment</button>
        
        <div class="back-link">
          <a href="cart.php">← Back to Cart</a>
        </div>
      </form>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
  
  <script>
    // Show/hide payment details based on selected method
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
      radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-details').forEach(detail => {
          detail.style.display = 'none';
        });
        
        const selectedMethod = this.value;
        if(selectedMethod === 'upi') {
          document.getElementById('upi-details').style.display = 'block';
          document.getElementById('card-details').style.display = 'none';
        } else if(selectedMethod === 'card') {
          document.getElementById('card-details').style.display = 'block';
          document.getElementById('upi-details').style.display = 'none';
        }
      });
    });
    
    // Format card number input
    const cardInput = document.querySelector('input[name="card_number"]');
    if(cardInput) {
      cardInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
      });
    }
    
    // Format expiry date
    const expiryInputs = document.querySelectorAll('input[placeholder="MM/YY"]');
    expiryInputs.forEach(input => {
      input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if(value.length >= 2) {
          value = value.slice(0, 2) + '/' + value.slice(2, 4);
        }
        e.target.value = value;
      });
    });
  </script>
</body>
</html>
