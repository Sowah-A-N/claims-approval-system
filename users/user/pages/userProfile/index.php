<?php
    // Set the page title
    $pageTitle = "User Profile";

    // Start the session
    session_start();

    // Include head partial
    include_once "../../assets/partials/head.php"; 

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 4;

    // Function to output full name stored in the session (if available)
    function outputFullName() {
        if(isset($_SESSION['full_name'])) {
            echo $_SESSION['full_name'];
        }
    }

    // Fetch user details
    if ($userId) {
        $userDetailsQuery = "SELECT * FROM user_details WHERE userId = $userId;";
        $userDetailsResult = mysqli_query($conn, $userDetailsQuery);
        $userDetailsRow = mysqli_fetch_assoc($userDetailsResult);
    }

    // Fetch user's existing bank details
    $bankDetailsQuery = "SELECT bank_name, bank_branch, account_name, account_number 
                        FROM user_bank_details WHERE userId = $userId;";
    $bankDetailsResult = mysqli_query($conn, $bankDetailsQuery);
    $bankDetails = mysqli_fetch_assoc($bankDetailsResult);

    // Store existing details in variables
    $existingBankName = $bankDetails['bank_name'] ?? '';
    $existingBranchName = $bankDetails['bank_branch'] ?? '';
    $existingAccountName = $bankDetails['account_name'] ?? '';
    $existingAccountNumber = $bankDetails['account_number'] ?? '';


    // Handle form submission for updating user details
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // if (isset($_POST['update_password'])) {
        //     // Handle updating password
        //     $newPassword = $_POST['new_password'];

        //     // Example update query (sanitize and validate inputs in a real scenario)
        //     $updatePasswordQuery = "UPDATE user_details SET password = '$newPassword' WHERE userId = $userId;";
        //     mysqli_query($conn, $updatePasswordQuery);

        //     // Redirect or show success message
        //     // Example redirect after update
        //     //header("Location: profile.php");
        //     exit();
        // }

        if (isset($_POST['update_password'])) {
            $oldPassword = mysqli_real_escape_string($conn, $_POST['old_password']);
            $newPassword = mysqli_real_escape_string($conn, $_POST['new_password']);
            $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        
            // Fetch the current password from the database
            $passwordQuery = "SELECT password FROM login_details WHERE userId = $userId;";
            $passwordResult = mysqli_query($conn, $passwordQuery);
            $passwordRow = mysqli_fetch_assoc($passwordResult);
            $currentPassword = $passwordRow['password'];
            echo $currentPassword;
        
            // Verify the old password matches
            //if (password_verify($oldPassword, $currentPassword)) {
            if($oldPassword == $currentPassword){
                // Check if new password matches confirm password
                if ($newPassword === $confirmPassword) {
                    // Hash the new password before updating
                    //$hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $hashedNewPassword = $newPassword;

                    // Update the password in the database
                    $updatePasswordQuery = "UPDATE login_details SET password = '$hashedNewPassword' WHERE userId = $userId;";
                    mysqli_query($conn, $updatePasswordQuery);
        
                    // Set success message
                    $_SESSION['message'] = "Password updated successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    // Set error message for non-matching passwords
                    $_SESSION['message'] = "New passwords do not match!";
                    $_SESSION['message_type'] = "error";
                }
            } else {
                // Set error message for incorrect old password
                $_SESSION['message'] = "Old password is incorrect!";
                $_SESSION['message_type'] = "error";
            }

             // Redirect to the same page to show the message
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        

        if (isset($_POST['update_bank_details'])) {
            // Handle updating bank details
            $bankName = mysqli_real_escape_string($conn, $_POST['bank_name']);
            $branchName = mysqli_real_escape_string($conn, $_POST['branch_name']);
            $accountName = mysqli_real_escape_string($conn, $_POST['account_name']);
            $accountNumber = mysqli_real_escape_string($conn, $_POST['account_number']);
        
            // Check if the user's bank details already exist
            $checkQuery = "SELECT * FROM user_bank_details WHERE userId = $userId";
            $checkResult = mysqli_query($conn, $checkQuery);
        
            if (mysqli_num_rows($checkResult) > 0) {
                // Record exists, perform an update
                $updateBankDetailsQuery = "UPDATE user_bank_details SET 
                                            bank_name = '$bankName', 
                                            bank_branch = '$branchName', 
                                            account_name = '$accountName', 
                                            account_number = '$accountNumber' 
                                            WHERE userId = $userId";
                mysqli_query($conn, $updateBankDetailsQuery);
            } else {
                // Record does not exist, insert a new one
                $insertBankDetailsQuery = "INSERT INTO user_bank_details (userId, bank_name, bank_branch, account_name, account_number) 
                                           VALUES ($userId, '$bankName', '$branchName', '$accountName', '$accountNumber')";
                mysqli_query($conn, $insertBankDetailsQuery);
            }
        
            // Redirect or show success message
            // Example redirect after update
            header("Location: .");
            //exit();
        }
    } 

    $banksAvailableQuery = "SELECT DISTINCT bank_name FROM `banks_branches`;";
    $banksAvailableResult = mysqli_query($conn, $banksAvailableQuery);

?>

<body>

<div class="container-scroller">
    <?php include "../../assets/partials/_sidebar.php" ?>

    <div class="container-fluid page-body-wrapper">
        <?php include "../../assets/partials/_navbar.php"; ?>

        <div class="main-panel">
            <div class="content-wrapper">

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo ($_SESSION['message_type'] == 'success') ? 'success' : 'danger'; ?>" role="alert">
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php 
                    // Clear the message after displaying it
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            <?php endif; ?>


                <!-- Change Password Section -->
                <h4>Change Password</h4>
                <form method="POST" action="">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="old_password">Old Password</label>
                        <div class="col-sm-9">
                            <input type="password" name="old_password" class="form-control" id="old_password" style="width:70%" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="new_password">New Password</label>
                        <div class="col-sm-9">
                            <input type="password" name="new_password" class="form-control" id="new_password" style="width:70%" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="confirm_password">Confirm New Password</label>
                        <div class="col-sm-9">
                            <input type="password" name="confirm_password" class="form-control" id="confirm_password" style="width:70%" required>
                        </div>
                    </div>
                    <div>
                        <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
                <br /><br />

                <!-- Bank Account Details Section -->
                <h4>Bank Account Details</h4>
                <form method="POST" action="">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="bank_name">Bank Name</label>
                        <div class="col-sm-9">
                            <select name="bank_name" class="form-control" id="bank_name" style="width:70%">
                            <?php
                                // Generate bank options from the query result
                                if ($banksAvailableResult && mysqli_num_rows($banksAvailableResult) > 0) {
                                    while ($row = mysqli_fetch_assoc($banksAvailableResult)) {
                                        $bankName = $row['bank_name'];
                                        $selected = ($existingBankName == $bankName) ? 'selected' : '';
                                        echo "<option value=\"$bankName\" $selected>$bankName</option>";
                                    }
                                } else {
                                    echo '<option value="">No banks available</option>';
                                }
                            ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="branch_name">Bank Branch</label>
                        <div class="col-sm-9">
                            <select name="branch_name" class="form-control" id="branch_name" style="width:70%">
                                <?php
                                    if (!empty($existingBranchName)) {
                                        echo "<option value=\"$existingBranchName\" selected>$existingBranchName</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="account_name">Account Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="account_name" class="form-control" id="account_name" style="width:70%" value="<?php echo $existingAccountName ?? ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label" for="account_number">Account Number</label>
                        <div class="col-sm-9">
                            <input type="text" name="account_number" class="form-control" id="account_number" style="width:70%" value="<?php echo $existingAccountNumber ?? ''; ?>" required>
                        </div>
                    </div>
                    <div>
                        <button type="submit" name="update_bank_details" class="btn btn-primary">Update Bank Details</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // When the bank dropdown value changes
        document.getElementById('bank_name').addEventListener('change', function() {
            var bankName = this.value;
            
            // Create a new XMLHttpRequest
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_branches.inc.php?bank_name=' + encodeURIComponent(bankName), true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Clear existing options in branch dropdown
                    var branchDropdown = document.getElementById('branch_name');
                    branchDropdown.innerHTML = '';
                    
                    // Parse the JSON response
                    console.log(xhr.responseText);
                    var branches = JSON.parse(xhr.responseText);
                    
                    // Append new options to branch dropdown
                    if (branches.length > 0) {
                        branches.forEach(function(branch) {
                            var option = document.createElement('option');
                            option.value = branch;
                            option.textContent = branch;
                            branchDropdown.appendChild(option);
                        });
                    } else {
                        var option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'No branches available';
                        branchDropdown.appendChild(option);
                    }
                }
            };
            xhr.send();
        });
    });


</script>
</body>

<?php include "../../assets/partials/_footer.html"; ?>

    <!-- plugins:js -->
    <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="../../assets/vendors/progressbar.js/progressbar.min.js"></script>
    <script src="../../assets/vendors/jvectormap/jquery-jvectormap.min.js"></script>
    <script src="../../assets/vendors/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="../../assets/js/off-canvas.js"></script>
    <script src="../../assets/js/misc.js"></script>
    <script src="../../assets/js/settings.js"></script>
    <script src="../../assets/js/todolist.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->

