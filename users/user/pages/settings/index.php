<?php
    // Set the page title
    $pageTitle = "Settings";

    // Start the session
    session_start();

    // Include head partial
    include_once "../../assets/partials/_head.php";

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

    $existingBankName    = $bankDetails['bank_name']      ?? '';
    $existingBranchName  = $bankDetails['bank_branch']    ?? '';
    $existingAccountName = $bankDetails['account_name']   ?? '';
    $existingAccountNumber = $bankDetails['account_number'] ?? '';

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
                <div class="rmu-alert rmu-alert--<?php echo ($_SESSION['message_type'] === 'success') ? 'success' : 'danger'; ?>" style="margin-bottom:20px;">
                    <?php echo h($_SESSION['message']); unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
                <?php endif; ?>

                <div class="rmu-page-header">
                    <div class="rmu-page-header__title">Settings</div>
                    <div class="rmu-page-header__sub">Manage your account password and bank details</div>
                </div>

                <!-- Change Password -->
                <div class="rmu-card" style="margin-bottom:24px;">
                    <div class="rmu-card__header">
                        <span class="rmu-card__title">Change Password</span>
                    </div>
                    <div class="rmu-card__body">
                        <form method="POST" action="" style="max-width:540px;">
                            <div class="rmu-form-group">
                                <label class="rmu-label" for="old_password">Current Password <span class="required">*</span></label>
                                <input type="password" name="old_password" class="rmu-input" id="old_password" required>
                            </div>
                            <div class="rmu-form-group">
                                <label class="rmu-label" for="new_password">New Password <span class="required">*</span></label>
                                <input type="password" name="new_password" class="rmu-input" id="new_password" required>
                            </div>
                            <div class="rmu-form-group">
                                <label class="rmu-label" for="confirm_password">Confirm New Password <span class="required">*</span></label>
                                <input type="password" name="confirm_password" class="rmu-input" id="confirm_password" required>
                            </div>
                            <button type="submit" name="update_password" class="rmu-btn rmu-btn--primary">Update Password</button>
                        </form>
                    </div>
                </div>

                <!-- Bank Account Details -->
                <div class="rmu-card" style="margin-bottom:24px;">
                    <div class="rmu-card__header">
                        <span class="rmu-card__title">Bank Account Details</span>
                    </div>
                    <div class="rmu-card__body">
                        <form method="POST" action="" style="max-width:540px;">
                            <div class="rmu-form-group">
                                <label class="rmu-label" for="bank_name">Bank Name</label>
                                <select name="bank_name" class="rmu-select" id="bank_name">
                                    <?php if ($banksAvailableResult && mysqli_num_rows($banksAvailableResult) > 0):
                                        while ($row = mysqli_fetch_assoc($banksAvailableResult)):
                                            $bn = $row['bank_name'];
                                            $sel = ($existingBankName === $bn) ? 'selected' : '';
                                            echo '<option value="' . h($bn) . '" ' . $sel . '>' . h($bn) . '</option>';
                                        endwhile;
                                    else: ?>
                                        <option value="">No banks available</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="rmu-form-group">
                                <label class="rmu-label" for="branch_name">Branch</label>
                                <select name="branch_name" class="rmu-select" id="branch_name">
                                    <?php if (!empty($existingBranchName)): ?>
                                        <option value="<?php echo h($existingBranchName); ?>" selected><?php echo h($existingBranchName); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="rmu-form-group">
                                <label class="rmu-label" for="account_name">Account Name <span class="required">*</span></label>
                                <input type="text" name="account_name" class="rmu-input" id="account_name" value="<?php echo h($existingAccountName); ?>" required>
                            </div>
                            <div class="rmu-form-group">
                                <label class="rmu-label" for="account_number">Account Number <span class="required">*</span></label>
                                <input type="text" name="account_number" class="rmu-input" id="account_number" value="<?php echo h($existingAccountNumber); ?>" required>
                            </div>
                            <button type="submit" name="update_bank_details" class="rmu-btn rmu-btn--primary">Update Bank Details</button>
                        </form>
                    </div>
                </div>

            </div>
            <?php include "../../assets/partials/_footer.php"; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('bank_name').addEventListener('change', function() {
            var bankName = this.value;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_branches.inc.php?bank_name=' + encodeURIComponent(bankName), true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var branchDropdown = document.getElementById('branch_name');
                    branchDropdown.innerHTML = '';
                    var branches = JSON.parse(xhr.responseText);
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



