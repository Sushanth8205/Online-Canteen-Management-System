<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// Get current admin level from session
$current_admin_level = $_SESSION['admin_level'] ?? 'admin';

// Helper function for file upload
function handleFileUpload() {
    $image_url = '';
    if(isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
        $upload_dir = 'images/';
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image_url']['name']);
        $target_file = $upload_dir . $file_name;
        
        if(move_uploaded_file($_FILES['image_url']['tmp_name'], $target_file)) {
            $image_url = $target_file;
        }
    }
    return $image_url;
}

// Handle POST requests
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['action']) && $_POST['action'] == 'update_order_status'){
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        
        $update_query = "UPDATE orders SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $order_id);
        mysqli_stmt_execute($update_stmt);
        
        header("Location: admin.php?page=orders");
        exit();
    }
}

// Handle AJAX requests first (before any HTML output)
if(isset($_GET['action']) && $_GET['action'] == 'get_dish'){
    $id = $_GET['id'];
    
    $query = "SELECT * FROM dishes WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dish = mysqli_fetch_assoc($result);
    
    header('Content-Type: application/json');
    echo json_encode($dish);
    exit();
}

// Get all dishes
$dishes_query = "SELECT * FROM dishes ORDER BY name";
$dishes_result = mysqli_query($connection, $dishes_query);

// Get all users for debugging
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($connection, $users_query);

// Get all admins from standalone admins table
$admins_query = "SELECT * FROM admins ORDER BY created_at DESC";
$admins_result = mysqli_query($connection, $admins_query);

// Handle menu item operations
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $action = $_POST['action'] ?? '';
    
    if($action == 'add_dish'){
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        $preparation_time = $_POST['preparation_time'] ?? 15;
        
        // Handle file upload
        $image_url = handleFileUpload();
        $dietary_info = $_POST['dietary_info'] ?? 'non-vegetarian';
        
        $insert_query = "INSERT INTO dishes (name, description, price, category, preparation_time, image_url, dietary_info) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssdsiss", $name, $description, $price, $category, $preparation_time, $image_url, $dietary_info);
        mysqli_stmt_execute($stmt);
        
        header("Location: admin.php?page=menu");
        exit();
    }
    
    if($action == 'update_dish'){
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        $preparation_time = $_POST['preparation_time'] ?? 15;
        
        // Handle file upload
        $image_url = handleFileUpload();
        if(empty($image_url)) {
            // Keep existing image if no new file uploaded
            $query = "SELECT image_url FROM dishes WHERE id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $existing_dish = mysqli_fetch_assoc($result);
            $image_url = $existing_dish['image_url'] ?? '';
        } else {
            // Delete old image if new image is uploaded
            $query = "SELECT image_url FROM dishes WHERE id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $existing_dish = mysqli_fetch_assoc($result);
            $old_image = $existing_dish['image_url'] ?? '';
            
            // Delete old image file if it exists
            if(!empty($old_image) && file_exists($old_image)) {
                unlink($old_image);
            }
        }
        $dietary_info = $_POST['dietary_info'] ?? 'non-vegetarian';
        $is_available = $_POST['is_available'] ?? 0;
        
        $update_query = "UPDATE dishes SET name=?, description=?, price=?, category=?, preparation_time=?, image_url=?, dietary_info=?, is_available=? WHERE id=?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "ssdsissii", $name, $description, $price, $category, $preparation_time, $image_url, $dietary_info, $is_available, $id);
        mysqli_stmt_execute($stmt);
        
        header("Location: admin.php?page=menu");
        exit();
    }
    
    if($action == 'add_user'){
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $role_of = $_POST['role_of'];
        $is_active = $_POST['is_active'];
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $insert_query = "INSERT INTO users (full_name, email, username, phone, password_hash, role_of, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "sssssss", $full_name, $email, $username, $phone, $password_hash, $role_of, $is_active);
        
        if(mysqli_stmt_execute($stmt)){
            header("Location: admin.php?page=users");
            exit();
        }
    }
    
    if($action == 'add_admin'){
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $admin_level = $_POST['admin_level'];
        $is_active = $_POST['is_active'];
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert admin record directly
        $insert_admin_query = "INSERT INTO admins (full_name, email, username, phone, password_hash, admin_level, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($connection, $insert_admin_query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $full_name, $email, $username, $phone, $password_hash, $admin_level, $is_active);
        
        if(mysqli_stmt_execute($stmt)){
            header("Location: admin.php?page=admins");
            exit();
        } else {
            echo "Error adding admin: " . mysqli_error($connection);
        }
    }
    
    if($action == 'delete_admin'){
        $id = $_POST['id'];
        
        // Delete admin record directly
        $delete_admin_query = "DELETE FROM admins WHERE id = ?";
        $stmt = mysqli_prepare($connection, $delete_admin_query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if(mysqli_stmt_execute($stmt)){
            header("Location: admin.php?page=admins");
            exit();
        } else {
            echo "Error deleting admin: " . mysqli_error($connection);
        }
    }
    
    if($action == 'delete_dish'){
        $id = $_POST['id'];
        $delete_query = "DELETE FROM dishes WHERE id = ?";
        $stmt = mysqli_prepare($connection, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        
        header("Location: admin.php?page=menu");
        exit();
    }
    
    if($action == 'delete_user'){
        $id = $_POST['id'];
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($connection, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        
        header("Location: admin.php?page=users");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Online Canteen System</title>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>
  
  <div class="admin-container">
    <div class="admin-layout">
      <!-- Left Sidebar -->
      <aside class="sidebar">
        <div class="sidebar-section">
          <h3>Admin Panel</h3>
          <ul>
            <li><a href="admin.php?page=menu">Menu Management</a></li>
            <li><a href="admin.php?page=orders">Order Management</a></li>
            <li><a href="admin.php?page=users">User Management</a></li>
            <li><a href="admin.php?page=admins">Admin Management</a></li>
          </ul>
        </div>
      </aside>
      
      <!-- Main Content -->
      <main class="main-content">
        <?php
        $page = $_GET['page'] ?? 'menu';
        
        if($page == 'menu'): ?>
          <!-- Menu Management Section -->
          <h2>Menu Management</h2>
          
          <!-- Add New Dish Form -->
          <div class="add-dish-form">
            <h3>Add New Dish</h3>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
              <input type="hidden" name="action" value="add_dish">
              <input type="hidden" name="page" value="menu">
              
              <div class="form-row">
                <input type="text" name="name" placeholder="Dish Name" required>
                <input type="number" name="price" step="0.01" placeholder="Price" required>
              </div>
              
              <div class="form-row">
                <input type="text" name="description" placeholder="Description" required>
                <select name="category" required>
                  <option value="">Select Category</option>
                  <option value="starter">Starter</option>
                  <option value="curry">Curry</option>
                  <option value="rice">Rice</option>
                  <option value="noodles">Noodles</option>
                  <option value="breakfast">Breakfast</option>
                </select>
              </div>
              
              <div class="form-row">
                <input type="file" name="image_url" placeholder="Image URL">
                <input type="number" name="preparation_time" placeholder="Prep Time (minutes)" min="1" max="60">
              </div>
              
              <div class="form-row">
                <select name="dietary_info">
                  <option value="vegetarian">Vegetarian</option>
                  <option value="non-vegetarian">Non-Vegetarian</option>
                  <option value="vegan">Vegan</option>
                  <option value="gluten-free">Gluten-Free</option>
                </select>
                <button type="submit">Add Dish</button>
              </div>
            </form>
          </div>
          
          <!-- Existing Dishes Table -->
          <div class="dishes-table">
            <h3>Existing Dishes</h3>
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Description</th>
                  <th>Price</th>
                  <th>Image</th>
                  <th>Category</th>
                  <th>Available</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while($dish = mysqli_fetch_assoc($dishes_result)): ?>
                <tr>
                  <td><?php echo $dish['name']; ?></td>
                  <td><?php echo $dish['description']; ?></td>
                  <td>₹<?php echo number_format($dish['price'], 2); ?></td>
                  <td><img src="<?php echo $dish['image_url']; ?>" alt="<?php echo $dish['name']; ?>" style="width: 50px; height: 50px; object-fit: cover;"></td>
                  <td><?php echo ucfirst($dish['category']); ?></td>
                  <td>
                    <span class="availability <?php echo $dish['is_available'] ? 'available' : 'unavailable'; ?>">
                      <?php echo $dish['is_available'] ? 'Available' : 'Unavailable'; ?>
                    </span>
                  </td>
                  <td>
                    <button width="70px" class="edit-btn" onclick="editDish(<?php echo $dish['id']; ?>)">Edit</button>
                    <button class="delete-btn" onclick="deleteDish(<?php echo $dish['id']; ?>)">Delete</button>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php elseif($page == 'users'): ?>
          <!-- User Management Section -->
          <h2>User Management</h2>
          
          <!-- Quick Actions -->
          <div class="quick-actions">
            <button class="quick-admin-btn" onclick="showAddUserForm()">+ Add User</button>
          </div>
          
          <!-- Add User Form -->
          <div class="add-user-form" id="addUserForm" style="display: none;">
            <h3>Add New User</h3>
            <form action="admin.php?page=users" method="POST">
              <input type="hidden" name="action" value="add_user">
              
              <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>
              </div>
              
              <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
              </div>
              
              <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
              </div>
              
              <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="phone">
              </div>
              
              <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
              </div>
              
              <div class="form-group">
                <label>Role:</label>
                <select name="role_of" required>
                  <option value="staff">Staff</option>
                  <option value="customer">Customer</option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Status:</label>
                <select name="is_active" required>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
              
              <button type="submit" class="add-btn">Add User</button>
            </form>
          </div>
          
          <div class="users-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Username</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                <tr>
                  <td><?php echo $user['id']; ?></td>
                  <td><?php echo $user['full_name']; ?></td>
                  <td><?php echo $user['email']; ?></td>
                  <td><?php echo $user['username']; ?></td>
                  <td>
                    <span class="role-badge <?php echo $user['role_of'] ?? 'customer'; ?>">
                      <?php echo ucfirst($user['role_of'] ?? 'customer'); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                      <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td>
                    <button class="delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php elseif($page == 'admins'): ?>
          <!-- Admin Management Section -->
          <h2>Admin Management</h2>
          
         <!-- Quick Actions -->
          <div class="quick-actions">
            <button class="quick-admin-btn" onclick="showAddAdminForm()">+ Add Admin User</button>
          </div>
          
          <!-- Add Admin Form -->
          <div class="add-user-form" id="addAdminForm" style="display: none;">
            <h3>Add New Admin</h3>
            <form action="admin.php?page=admins" method="POST">
              <input type="hidden" name="action" value="add_admin">
              
              <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>
              </div>
              
              <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
              </div>
              
              <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
              </div>
              
              <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="phone">
              </div>
              
              <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
              </div>
              
              <div class="form-group">
                <label>Admin Level:</label>
                <select name="admin_level" required>
                  <option value="admin">Admin</option>
                  <option value="super_admin">Super Admin</option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Status:</label>
                <select name="is_active" required>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
              
              <button type="submit" class="add-btn">Add Admin</button>
            </form>
          </div>
          
          <div class="admins-table">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Username</th>
                  <th>Admin Level</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while($admin = mysqli_fetch_assoc($admins_result)): ?>
                <tr>
                  <td><?php echo $admin['id']; ?></td>
                  <td><?php echo $admin['full_name']; ?></td>
                  <td><?php echo $admin['email']; ?></td>
                  <td><?php echo $admin['username']; ?></td>
                  <td>
                    <span class="admin-level-badge <?php echo $admin['admin_level']; ?>">
                      <?php echo ucwords(str_replace('_', ' ', $admin['admin_level'])); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status <?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                      <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                  <td>
                    <?php 
                    // Only super admins can delete other admins, but not super admins themselves
                    if(isset($_SESSION['admin_level']) && $_SESSION['admin_level'] == 'super_admin' && $admin['admin_level'] != 'super_admin'): 
                    ?>
                      <button class="delete-btn" onclick="deleteAdmin(<?php echo $admin['id']; ?>)">Remove</button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php elseif($page == 'orders'): ?>
          <!-- Order Management Section -->
          <h2>Order Management</h2>
          
          <div class="orders-section">
            <?php
            // Get all orders with user details
            $orders_query = "SELECT o.*, u.username, u.full_name, u.role_of 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            ORDER BY o.created_at DESC";
            $orders_result = mysqli_query($connection, $orders_query);
            
            if(mysqli_num_rows($orders_result) > 0):
            ?>
              <div class="orders-table">
                <table>
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>Customer</th>
                      <th>Role</th>
                      <th>Total</th>
                      <th>Status</th>
                      <th>Payment</th>
                      <th>Seat</th>
                      <th>Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                      <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td>
                          <?php echo htmlspecialchars($order['full_name']); ?>
                          <br><small>(<?php echo htmlspecialchars($order['username']); ?>)</small>
                        </td>
                        <td>
                          <span class="role-badge <?php echo $order['role_of']; ?>">
                            <?php echo ucfirst($order['role_of']); ?>
                          </span>
                        </td>
                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                          <span class="status-badge <?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                          </span>
                        </td>
                        <td><?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></td>
                        <td>
                          <?php if($order['seat_number']): ?>
                            <span class="seat-badge">🪑 <?php echo $order['seat_number']; ?></span>
                          <?php else: ?>
                            -
                          <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
                        <td>
                          <button class="view-btn" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">View</button>
                          <?php if($order['status'] == 'pending'): ?>
                            <button class="confirm-btn" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed')">Confirm</button>
                          <?php elseif($order['status'] == 'confirmed'): ?>
                            <button class="complete-btn" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')">Complete</button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="no-orders">
                <p>No orders found.</p>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>
  
  <!-- Edit Modal -->
  <div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h3>Edit Dish</h3>
      <form id="editForm" method="POST" action="admin.php?page=menu" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_dish">
        <input type="hidden" name="id" id="editId">
        
        <div class="form-row">
          <input type="text" name="name" id="editName" placeholder="Dish Name" required>
          <input type="number" name="price" id="editPrice" step="0.01" placeholder="Price" required>
        </div>
        
        <div class="form-row">
          <input type="text" name="description" id="editDescription" placeholder="Description" required>
          <select name="category" id="editCategory" required>
            <option value="">Select Category</option>
            <option value="starter">Starter</option>
            <option value="curry">Curry</option>
            <option value="rice">Rice</option>
            <option value="noodles">Noodles</option>
            <option value="breakfast">Breakfast</option>
          </select>
        </div>
        
        <div class="form-row">
          <input type="file" name="image_url" id="editImageUrl">
          <input type="number" name="preparation_time" id="editPrepTime" placeholder="Prep Time (minutes)" min="1" max="60">
        </div>
        
        <div class="form-row">
          <select name="dietary_info" id="editDietary">
            <option value="vegetarian">Vegetarian</option>
            <option value="non-vegetarian">Non-Vegetarian</option>
            <option value="vegan">Vegan</option>
            <option value="gluten-free">Gluten-Free</option>
          </select>
          <label>
            <input type="checkbox" name="is_available" id="editAvailable" value="1">
            Available
          </label>
        </div>
        
        <button type="submit">Update Dish</button>
      </form>
    </div>
  </div>
  
  <script>
    function editDish(id) {
      // Fetch dish data and populate form
      fetch('admin.php?action=get_dish&id=' + id)
        .then(response => response.json())
        .then(data => {
          document.getElementById('editId').value = data.id;
          document.getElementById('editName').value = data.name;
          document.getElementById('editPrice').value = data.price;
          document.getElementById('editDescription').value = data.description;
          document.getElementById('editCategory').value = data.category;
          document.getElementById('editPrepTime').value = data.preparation_time;
          document.getElementById('editDietary').value = data.dietary_info;
          document.getElementById('editAvailable').checked = data.is_available == 1;
          
          document.getElementById('editModal').style.display = 'block';
        })
        .catch(error => {
          alert('Error loading dish data. Please try again.');
        });
    }
    
    function createDeleteForm(action, id) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'admin.php';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = action;
      
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'id';
      idInput.value = id;
      
      form.appendChild(actionInput);
      form.appendChild(idInput);
      document.body.appendChild(form);
      form.submit();
    }
    
    function deleteDish(id) {
      if(confirm('Are you sure you want to delete this dish?')) {
        createDeleteForm('delete_dish', id);
      }
    }
    
    function deleteUser(id) {
      if(confirm('Are you sure you want to delete this user?')) {
        createDeleteForm('delete_user', id);
      }
    }
    
    function deleteAdmin(id) {
      if(confirm('Are you sure you want to remove this admin?')) {
        createDeleteForm('delete_admin', id);
      }
    }
    
    function viewOrderDetails(orderId) {
      // Create a modal or redirect to order details page
      window.open('order_details.php?id=' + orderId, '_blank', 'width=800,height=600');
    }
    
    function updateOrderStatus(orderId, newStatus) {
      if(confirm('Are you sure you want to update order status to ' + newStatus + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_order_status';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'order_id';
        orderIdInput.value = orderId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = newStatus;
        
        form.appendChild(actionInput);
        form.appendChild(orderIdInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
      }
    }
    
    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }
    
    function showAddAdminForm() {
      document.getElementById('addAdminForm').style.display = 'block';
    }
    
    function showAddUserForm() {
      document.getElementById('addUserForm').style.display = 'block';
    }
    
    function hideAddAdminForm() {
      document.getElementById('addAdminForm').style.display = 'none';
    }
    
    function hideAddUserForm() {
      document.getElementById('addUserForm').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>
</html>
