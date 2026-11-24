<?php
require "config/init.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $favorite_user_id = (int)$_POST['user_id'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Check if already in favorites
    $check = mysqli_query($conn, "SELECT id FROM user_favorites WHERE user_id = $user_id AND favorite_user_id = $favorite_user_id");
    
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO user_favorites (user_id, favorite_user_id, notes, status) 
                VALUES ($user_id, $favorite_user_id, '$notes', 'active')";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Added to favorites']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Already in favorites']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>