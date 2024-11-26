<?php 
    define('DB_NAME', "doc-app");
    define('DB_HOST', "localhost");
    define('DB_USER', "doc-app");
    define('DB_PASS', "CUBEX1nscc");

    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if(!$conn){
        echo "Database connection could not be established. Is server alive?" . mysqli_connect_error();
    } elseif ($conn){
        mysqli_set_charset($conn, "utf8mb4");
    }