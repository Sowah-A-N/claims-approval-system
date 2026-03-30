<?php
require_once __DIR__ . '/../../../includes/headers.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';
checkUserRole(['approver', 'Approver']);
?>

<!DOCTYPE html>
<html lang="en">

    
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?></title>
    <link rel="shortcut icon" type="image/png" href="../images/logos/favicon.png" />
    <link rel="stylesheet" href="../approver/assets/css/styles.min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="./assets/js/jquery-3.7.1.min.js"></script>
</head>
