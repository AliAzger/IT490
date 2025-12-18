<?php
// UserStore.php

/**
 * Create a new user with hashed password
 */
function createUser($username, $password) {
    if (empty($username) || empty($password)) {
        throw new Exception("Username and password cannot be empty");
    }
    
    $usersFile = __DIR__ . '/data/users.json';
    
    // Create data directory if it doesn't exist
    if (!file_exists(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0755, true);
    }
    
    // Load existing users
    $users = [];
    if (file_exists($usersFile)) {
        $content = file_get_contents($usersFile);
        $users = json_decode($content, true) ?? [];
    }
    
    // Check if user already exists
    if (isset($users[$username])) {
        throw new Exception("User already exists");
    }
    
    // Hash password and store user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $users[$username] = [
        'password' => $hashedPassword,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Save users file
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    
    return true;
}

/**
 * Verify user credentials
 */
function verifyUser($username, $password) {
    if (empty($username) || empty($password)) {
        return false;
    }
    
    $usersFile = __DIR__ . '/data/users.json';
    
    // Check if users file exists
    if (!file_exists($usersFile)) {
        return false;
    }
    
    // Load users
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true) ?? [];
    
    // Check if user exists
    if (!isset($users[$username])) {
        return false;
    }
    
    // Verify password
    return password_verify($password, $users[$username]['password']);
}

/**
 * Get user information (without password)
 */
function getUser($username) {
    $usersFile = __DIR__ . '/data/users.json';
    
    if (!file_exists($usersFile)) {
        return null;
    }
    
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true) ?? [];
    
    if (!isset($users[$username])) {
        return null;
    }
    
    // Return user info without password
    return [
        'username' => $username,
        'created_at' => $users[$username]['created_at']
    ];
}

/**
 * Delete a user
 */
function deleteUser($username) {
    $usersFile = __DIR__ . '/data/users.json';
    
    if (!file_exists($usersFile)) {
        throw new Exception("User not found");
    }
    
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true) ?? [];
    
    if (!isset($users[$username])) {
        throw new Exception("User not found");
    }
    
    unset($users[$username]);
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    
    return true;
}
?>