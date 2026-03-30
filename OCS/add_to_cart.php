<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Handle add to cart request
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dish_id'])){
    $dish_id = $_POST['dish_id'];
    
    // Check if dish is available
    $query = "SELECT * FROM dishes WHERE id = ? AND is_available = 1";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $dish_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($dish = mysqli_fetch_assoc($result)){
        // Check if item already exists in cart
        $check_query = "SELECT * FROM cart_items WHERE user_id = ? AND dish_id = ?";
        $check_stmt = mysqli_prepare($connection, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $_SESSION['user_id'], $dish_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if(mysqli_num_rows($check_result) > 0){
            // Update existing item quantity
            $update_query = "UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND dish_id = ?";
            $update_stmt = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ii", $_SESSION['user_id'], $dish_id);
            mysqli_stmt_execute($update_stmt);
        } else {
            // Insert new item into cart
            $insert_query = "INSERT INTO cart_items (user_id, dish_id, quantity) VALUES (?, ?, 1)";
            $insert_stmt = mysqli_prepare($connection, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ii", $_SESSION['user_id'], $dish_id);
            mysqli_stmt_execute($insert_stmt);
        }
        
        $_SESSION['toast_message'] = $dish['name'] . " added to cart!";
    }
    
    mysqli_stmt_close($stmt);
    
    // Redirect back to menu
    header("Location: menu.php");
    exit();
}

// If not a POST request, redirect to menu
header("Location: menu.php");
exit();
?>
