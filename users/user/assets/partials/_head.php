<?php

	if (session_status() === PHP_SESSION_NONE) {
	// Configure session settings
	ini_set('session.gc_maxlifetime', 3600); // Set the garbage collection lifetime to 3600 seconds (1 hour)

	session_set_cookie_params([
		'lifetime' => 3600,  // Lifetime of the cookie in seconds
		'path' => '/',       // Path on the server where the cookie will be available
		'domain' => '',      // Domain for which the cookie is available (default is current domain)
		'secure' => false,  // Whether to only send the cookie over secure connections
		'httponly' => true, // Whether to make the cookie accessible only through the HTTP protocol
	]);

		session_start();
	}

	// Regenerate session ID to prevent session fixation attacks
	session_regenerate_id(true);

	// Function to check if the user is logged in
	function isUserLoggedIn() {
		return isset($_SESSION['user_id']);
	}

	// Function to check user role and redirect if unauthorized
	function checkUserRole(array $allowedRole) {
		if (!isUserLoggedIn() || !in_array($_SESSION['role'], $allowedRole)) {
			header("Location: /");
			exit();
		}
	}

	checkUserRole(['user', 'claimant']);
	include_once '../../includes/conn.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $pageTitle?></title>
    <!-- plugins:css -->
	 <?php 
		$basePath = ($pageTitle == "Dashboard") ? './' : '../../';
	?>

	<link rel="stylesheet" href="<?php echo $basePath; ?>assets/vendors/feather/feather.css">
	<link rel="stylesheet" href="<?php echo $basePath; ?>assets/vendors/ti-icons/css/themify-icons.css">
	<link rel="stylesheet" href="<?php echo $basePath; ?>assets/vendors/css/vendor.bundle.base.css">
	<link rel="stylesheet" href="<?php echo $basePath; ?>assets/vendors/font-awesome/css/font-awesome.min.css">
	<link rel="stylesheet" href="<?php echo $basePath; ?>assets/vendors/mdi/css/materialdesignicons.min.css">
	<!-- endinject -->
	<!-- Plugin css for this page -->
	<!-- <link rel="stylesheet" href="<?php echo $basePath; ?>assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css"> -->
	<link rel="stylesheet" href="<?php echo $basePath; ?>assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css">
	<link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>assets/js/select.dataTables.min.css">
	<!-- End plugin css for this page -->
	<!-- inject:css -->
	<link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
	<!-- endinject -->
	<link rel="shortcut icon" href="<?php echo $basePath; ?>assets/images/favicon.png" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" 
			integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" 
			crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js" 
			integrity="sha512-ykZ1QQr0Jy/4ZkvKuqWn4iF3lqPZyij9iRv6sGqLRdTPkY69YX6+7wvVGmsdBbiIfN/8OdsI7HABjvEok6ZopQ=="
			crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.11.0/dist/sweetalert2.all.min.js" 
            integrity="sha256-4lhPGIWv8kmCP7JRGJE4IdRod2IdQEZPui6f0uICZ6w=" 
            crossorigin="anonymous"></script>
   
  </head>
	
	