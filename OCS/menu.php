<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Get dishes from database
$query = "SELECT * FROM dishes WHERE is_available = 1 ORDER BY name";
$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu - Online Canteen System</title>
  <link rel="stylesheet" href="css/menu.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  
  <div class="menu-container">
    <h1>Our Menu</h1>
    
    <?php if(mysqli_num_rows($result) > 0): ?>
      <div class="menu-grid">
        <?php while($dish = mysqli_fetch_assoc($result)): ?>
          <div class="dish-card">
            <div class="dish-image">
              <?php 
              $image_path = !empty($dish['image_url']) ? $dish['image_url'] : 'images/placeholder.jpg';
              ?>
              <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($dish['name']); ?>">
            </div>
            <div class="dish-info">
              <h3><?php echo htmlspecialchars($dish['name']); ?></h3>
              <p><?php echo htmlspecialchars($dish['description']); ?></p>
              <div class="dish-footer">
                <span class="price">₹<?php echo number_format($dish['price'], 2); ?></span>
                <?php if($_SESSION['user_role'] != 'admin'): ?>
                <form action="add_to_cart.php" method="post" style="display: inline;">
                  <input type="hidden" name="dish_id" value="<?php echo $dish['id']; ?>">
                  <button type="submit" class="btn">Add to Cart</button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="no-dishes">
        <h2>No dishes available at the moment.</h2>
        <p>Please check back later!</p>
      </div>
    <?php endif; ?>
  </div>

  <?php include 'includes/footer.php'; ?>
  
  <!-- Toast Notification -->
  <div id="toast" class="toast">
    <div class="toast-content">
      <span id="toast-message">Item added to cart successfully!</span>
      <button class="toast-close" onclick="hideToast()">×</button>
    </div>
  </div>

  <script>
    // Show toast notification if message exists
    <?php if(isset($_SESSION['toast_message'])): ?>
      document.addEventListener('DOMContentLoaded', function() {
        showToast('<?php echo $_SESSION['toast_message']; ?>');
        <?php unset($_SESSION['toast_message']); ?>
      });
    <?php endif; ?>

    function showToast(message) {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toast-message');
      toastMessage.textContent = message;
      toast.classList.add('show');
      
      // Auto hide after 3 seconds
      setTimeout(() => {
        hideToast();
      }, 3000);
    }

    function hideToast() {
      const toast = document.getElementById('toast');
      toast.classList.remove('show');
    }
  </script>
</body>
</html>
