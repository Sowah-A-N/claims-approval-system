<?php 
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'doc-app');
    define('DB_USER', 'doc-app');
    define('DB_PASS', 'CUBEX1nscc');

    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (!$conn){
        echo "Error connecting to database! Is server alive? " . mysqli_connect_error();
    } else {
        mysqli_set_charset($conn, "utf8mb4");
    } 