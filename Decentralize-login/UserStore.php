<?php
// UserStore.php

function loadUsers($file = "users.json") {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true);
}

function saveUsers($users, $file = "users.json") {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

function createUser($username, $password) {
    $users = loadUsers();
    if (isset($users[$username])) {
        throw new Exception("User already exists: $username");
    }
    $users[$username] = password_hash($password, PASSWORD_BCRYPT);
    saveUsers($users);
}

function verifyUser($username, $password) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    return password_verify($password, $users[$username]);
}
