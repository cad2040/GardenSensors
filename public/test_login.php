<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

try {
    // Get user from database
    $db = new Database();
    $conn = $db->getConnection();
    
    $email = 'admin@example.com';
    $password = 'password';
    
    echo "Testing login with:\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n\n";
    
    $query = "SELECT * FROM users WHERE email = :email AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User found in database:\n";
    print_r($user);
    echo "\n";
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $passwordValid = password_verify($password, $user['password']);
    echo "Password verification result: " . ($passwordValid ? "Valid" : "Invalid") . "\n";
    
    if (!$passwordValid) {
        throw new Exception('Invalid password');
    }
    
    echo "\nLogin successful!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 