<?php
    // Set the page title
    $pageTitle = "User Profile";

    // Start the session
    session_start();

    // Include head partial
    require_once __DIR__ . '/../../assets/partials/_head.php';

    $userId = current_user_id();

    function outputFullName() {
        echo isset($_SESSION['full_name']) ? h($_SESSION['full_name']) : '';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['update_password'])) {
            $stmt = mysqli_prepare($conn, "SELECT password FROM login_details WHERE userId = ?");
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            $currentHash = $row['password'] ?? '';

            $oldPassword     = $_POST['old_password']     ?? '';
            $newPassword     = $_POST['new_password']     ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!password_verify($oldPassword, $currentHash)) {
                $_SESSION['message']      = "Old password is incorrect!";
                $_SESSION['message_type'] = "error";
            } elseif ($newPassword !== $confirmPassword) {
                $_SESSION['message']      = "New passwords do not match!";
                $_SESSION['message_type'] = "error";
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE login_details SET password = ? WHERE userId = ?");
                mysqli_stmt_bind_param($stmt, 'si', $hash, $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $_SESSION['message']      = "Password updated successfully!";
                $_SESSION['message_type'] = "success";
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        if (isset($_POST['update_bank_details'])) {
            $bankName      = $_POST['bank_name']      ?? '';
            $branchName    = $_POST['branch_name']    ?? '';
            $accountName   = $_POST['account_name']   ?? '';
            $accountNumber = $_POST['account_number'] ?? '';

            $chk = mysqli_prepare($conn, "SELECT 1 FROM user_bank_details WHERE userId = ?");
            mysqli_stmt_bind_param($chk, 'i', $userId);
            mysqli_stmt_execute($chk);
            $chkResult = mysqli_stmt_get_result($chk);
            $exists = mysqli_num_rows($chkResult) > 0;
            mysqli_stmt_close($chk);

            if ($exists) {
                $stmt = mysqli_prepare($conn,
                    "UPDATE user_bank_details SET bank_name=?, bank_branch=?, account_name=?, account_number=? WHERE userId=?");
                mysqli_stmt_bind_param($stmt, 'ssssi', $bankName, $branchName, $accountName, $accountNumber, $userId);
            } else {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO user_bank_details (userId, bank_name, bank_branch, account_name, account_number) VALUES (?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, 'issss', $userId, $bankName, $branchName, $accountName, $accountNumber);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: .");
            exit();
        }
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM user_details WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $userDetailsRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn,
        "SELECT bank_name, bank_branch, account_name, account_number FROM user_bank_details WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $bankDetails = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $existingBankName      = $bankDetails['bank_name']       ?? '';
    $existingBranchName    = $bankDetails['bank_branch']     ?? '';
    $existingAccountName   = $bankDetails['account_name']    ?? '';
    $existingAccountNumber = $bankDetails['account_number']  ?? '';

    $banksAvailableResult = mysqli_query($conn, "SELECT DISTINCT bank_name FROM banks_branches ORDER BY bank_name ASC");

?>

<body>

<div class="container-scroller">
    <?php include "../../assets/partials/_navbar.php"; ?>

    <div class="container-fluid page-body-wrapper">
        <?php include "../../assets/partials/_sidebar.php" ?>

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
            xhr.open('GET', '../settings/fetch_branches.inc.php?bank_name=' + encodeURIComponent(bankName), true);
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

<?php include "../../assets/partials/_footer.php"; ?>

