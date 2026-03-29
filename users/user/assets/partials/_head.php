<?php
	require_once __DIR__ . '/../../../../includes/auth.php';
	require_once __DIR__ . '/../../../../includes/db.php';
	checkUserRole(['user', 'claimant']);
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
	
	