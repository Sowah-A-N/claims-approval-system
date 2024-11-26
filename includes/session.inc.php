<?php
session_start();

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
        header("Location: unauthorized.php");
        exit();
    }
}

// // Function to get dashboard link and text based on user role
// function getDashboardInfo($userRole) {
//     $dashboardInfo = [
//         'admin' => [
//             'link' => 'admin_dashboard.php',
//             'text' => 'Admin Dashboard'
//         ],
//         'user' => [
//             'link' => 'user_dashboard.php',
//             'text' => 'User Dashboard'
//         ],
//         'approver' => [
//             'link' => 'approver_dashboard.php',
//             'text' => 'Approver Dashboard'
//         ]
//     ];

//     return $dashboardInfo[$userRole] ?? [
//         'link' => 'user_dashboard.php',
//         'text' => 'User Dashboard'
//     ];
// }

// // Example: Simulate user login (replace with your authentication logic)
// $_SESSION['user_id'] = 123;
// $_SESSION['username'] = 'john_doe';
// $_SESSION['user_role'] = 'admin'; // or 'user', 'moderator', etc.

// Check user role for specific page access
// checkUserRole(['admin', 'user', 'approver']);
checkUserRole('user');
// Get dashboard link and text based on user role
$userRole = $_SESSION['role'];
// $dashboardInfo = getDashboardInfo($userRole);

// $dashboardLink = $dashboardInfo['link'];
// $dashboardText = $dashboardInfo['text'];

// Example output (for debugging or display purposes)
// echo "<a href='$dashboardLink'>$dashboardText</a>";
