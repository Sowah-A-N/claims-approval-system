<?php

      include_once '../../../../includes/conn.inc.php';

      $department = sanitizeInput($conn, $_POST['department']) ?? "";
      $programme = sanitizeInput($conn, $_POST['programme']) ?? "";
      $course = sanitizeInput($conn, $_POST['course']) ?? "";
      

     if (isset($_POST['submitBtn'])) {

        switch ($_POST['submitBtn']) {
           case "Save Claim Information":
                 echo "Saving claim info for later....";
                 break;

           case "Submit Claim":
                  $stage = 1;

                  $submitClaimSql = "INSERT INTO claim_details (department, programme, course, stage) 
                                    VALUES (?, ?, ?, ?)";

                  $stmt = $conn -> prepare($submitClaimSql);
                  $stmt -> bind_param("sssi", $department, $programme, $course, $stage);

                  $submitClaimResult = mysqli_stmt_execute($stmt);

                  if (!$submitClaimResult){
                        echo "Error submitting claim : " . $stmt -> error;

                  } else {
                        echo "<script>alert(\"Data submitted successfully!\")</script>";
                        $claim_id = mysqli_stmt_insert_id($stmt);

                        $submitClaimDataSql = "INSERT INTO claim_data (claim_id, date, start_time, end_time, periods) 
                                    VALUES (?, ?, ?, ?, ?)";
                  };

                  print "Claim info has been submitted!";
                  break;
          
        }
    }

    function sanitizeInput($conn, $input){
      return htmlspecialchars(mysqli_real_escape_string($conn, $input));
    }