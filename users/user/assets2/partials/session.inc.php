<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}  

// Set session timeout to 30 minutes
ini_set('session.gc_maxlifetime', 1800);
// Optionally, set the cookie lifetime to match the session timeout
session_set_cookie_params(1800);

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Function to check if the user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role and redirect if unauthorized
function checkUserRole($allowedRole) {
    if (!isUserLoggedIn() || !in_array($_SESSION['role'], $allowedRole)) {
        header("Location: ../pages/403.php");
        exit();
    }
}

checkUserRole('user');

// Get dashboard link and text based on user role
$userRole = $_SESSION['role'];

