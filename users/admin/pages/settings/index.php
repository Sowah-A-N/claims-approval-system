<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
checkUserRole(['admin', 'Admin']);
csrf_token();

$flash_success = '';
$flash_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['add_bank'])) {
        $new_bank_name = strtoupper(validated_str(isset($_POST['new_bank_name']) ? $_POST['new_bank_name'] : ''));
        $branch_code   = strtoupper(validated_str(isset($_POST['branch_code'])   ? $_POST['branch_code']   : ''));
        $branch_name   = strtoupper(validated_str(isset($_POST['branch_name'])   ? $_POST['branch_name']   : ''));

        if ($new_bank_name === '' || $branch_code === '' || $branch_name === '') {
            $flash_error = 'All fields are required.';
        } else {
            $chk = mysqli_prepare($conn, 'SELECT COUNT(*) FROM banks_branches WHERE bank_name = ?');
            mysqli_stmt_bind_param($chk, 's', $new_bank_name);
            mysqli_stmt_execute($chk);
            $cnt = mysqli_fetch_row(mysqli_stmt_get_result($chk))[0];
            mysqli_stmt_close($chk);

            if ($cnt > 0) {
                $flash_error = 'Bank name already exists.';
            } else {
                $chk2 = mysqli_prepare($conn, 'SELECT COUNT(*) FROM banks_branches WHERE branch_code = ?');
                mysqli_stmt_bind_param($chk2, 's', $branch_code);
                mysqli_stmt_execute($chk2);
                $cnt2 = mysqli_fetch_row(mysqli_stmt_get_result($chk2))[0];
                mysqli_stmt_close($chk2);

                if ($cnt2 > 0) {
                    $flash_error = 'Branch code already exists.';
                } else {
                    $ins = mysqli_prepare($conn,
                        'INSERT INTO banks_branches (branch_code, bank_name, bank_branch) VALUES (?, ?, ?)');
                    mysqli_stmt_bind_param($ins, 'sss', $branch_code, $new_bank_name, $branch_name);
                    if (mysqli_stmt_execute($ins)) {
                        $flash_success = 'Bank and branch added successfully.';
                    } else {
                        $flash_error = 'Database error. Please try again.';
                    }
                    mysqli_stmt_close($ins);
                }
            }
        }

    } elseif (isset($_POST['add_branch'])) {
        $branch_code = strtoupper(validated_str(isset($_POST['branch_code']) ? $_POST['branch_code'] : ''));
        $bank_name   = validated_str(isset($_POST['bank_name'])   ? $_POST['bank_name']   : '');
        $bank_branch = strtoupper(validated_str(isset($_POST['bank_branch']) ? $_POST['bank_branch'] : ''));

        if ($branch_code === '' || $bank_name === '' || $bank_branch === '') {
            $flash_error = 'All fields are required.';
        } else {
            $ins = mysqli_prepare($conn,
                'INSERT INTO banks_branches (branch_code, bank_name, bank_branch) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($ins, 'sss', $branch_code, $bank_name, $bank_branch);
            if (mysqli_stmt_execute($ins)) {
                $flash_success = 'Branch added successfully.';
            } else {
                $flash_error = 'Branch code may already exist.';
            }
            mysqli_stmt_close($ins);
        }

    } elseif (isset($_POST['remove_branch'])) {
        $bank_branch_id = (int)(isset($_POST['bank_branch_id']) ? $_POST['bank_branch_id'] : 0);
        if ($bank_branch_id > 0) {
            $del = mysqli_prepare($conn, 'DELETE FROM banks_branches WHERE bank_branch_id = ?');
            mysqli_stmt_bind_param($del, 'i', $bank_branch_id);
            if (mysqli_stmt_execute($del)) {
                $flash_success = 'Branch removed successfully.';
            } else {
                $flash_error = 'Could not remove branch.';
            }
            mysqli_stmt_close($del);
        } else {
            $flash_error = 'Invalid branch ID.';
        }
    }
}

// Fetch data for display
$selected_bank = '';
if (isset($_GET['bank_name']) && $_GET['bank_name'] !== '') {
    $selected_bank = validated_str($_GET['bank_name']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bank_name']) && $_POST['bank_name'] !== '') {
    $selected_bank = validated_str($_POST['bank_name']);
}

$banks_stmt = mysqli_prepare($conn, 'SELECT DISTINCT bank_name FROM banks_branches ORDER BY bank_name');
mysqli_stmt_execute($banks_stmt);
$banks = mysqli_fetch_all(mysqli_stmt_get_result($banks_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($banks_stmt);

$branches = [];
if ($selected_bank !== '') {
    $br_stmt = mysqli_prepare($conn, 'SELECT * FROM banks_branches WHERE bank_name = ? ORDER BY bank_branch');
    mysqli_stmt_bind_param($br_stmt, 's', $selected_bank);
    mysqli_stmt_execute($br_stmt);
    $branches = mysqli_fetch_all(mysqli_stmt_get_result($br_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($br_stmt);
}

$fuel_stmt = mysqli_prepare($conn,
    "SELECT settingValue FROM settings WHERE settingName = 'fuelComponent' LIMIT 1");
mysqli_stmt_execute($fuel_stmt);
$fuel_row = mysqli_fetch_row(mysqli_stmt_get_result($fuel_stmt));
mysqli_stmt_close($fuel_stmt);
$fuelComponent = $fuel_row ? (int)$fuel_row[0] : 0;

$rates_stmt = mysqli_prepare($conn, 'SELECT * FROM lecturer_rank_rate ORDER BY rankId');
mysqli_stmt_execute($rates_stmt);
$rates = mysqli_fetch_all(mysqli_stmt_get_result($rates_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($rates_stmt);

$pageTitle = "Settings";
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../../assets/partials/head.php'; ?>
<body>
<div class="page-wrapper" id="main-wrapper">
    <?php include '../../assets/partials/sidebar.php'; ?>

    <div class="body-wrapper">
        <?php include '../../assets/partials/header.php'; ?>

        <div style="padding:28px 32px;">

            <div class="rmu-page-header">
                <div class="rmu-page-header__title">Settings</div>
                <div class="rmu-page-header__sub">Manage claim rates, system settings, and banking information</div>
            </div>

            <?php if ($flash_success): ?>
            <div class="rmu-alert rmu-alert--success" style="margin-bottom:20px;">
                <i class="ti ti-circle-check"></i> <?php echo h($flash_success); ?>
            </div>
            <?php endif; ?>
            <?php if ($flash_error): ?>
            <div class="rmu-alert rmu-alert--danger" style="margin-bottom:20px;">
                <i class="ti ti-alert-circle"></i> <?php echo h($flash_error); ?>
            </div>
            <?php endif; ?>

            <!-- Lecturer Rates -->
            <div class="rmu-card" style="margin-bottom:24px;">
                <div class="rmu-card__header">
                    <span class="rmu-card__title"><i class="ti ti-cash" style="margin-right:8px;"></i>Lecturer Claim Rates</span>
                </div>
                <div class="rmu-card__body">
                    <form id="updateRatesForm" method="POST" action="updateRates.inc.php">
                        <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:20px;">
                            <?php foreach ($rates as $row): ?>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label"><?php echo h($row['rank']); ?></label>
                                <input type="number" step="0.01" min="0"
                                       name="rate_<?php echo (int)$row['rankId']; ?>"
                                       class="rmu-input"
                                       value="<?php echo h($row['rate']); ?>">
                                <input type="hidden" name="id_<?php echo (int)$row['rankId']; ?>"
                                       value="<?php echo (int)$row['rankId']; ?>">
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($rates)): ?>
                            <p style="color:var(--txt-muted);">No ranks found.</p>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="rmu-btn rmu-btn--primary">
                            <i class="ti ti-device-floppy"></i> Update Rates
                        </button>
                    </form>
                </div>
            </div>

            <!-- General Settings -->
            <div class="rmu-card" style="margin-bottom:24px;">
                <div class="rmu-card__header">
                    <span class="rmu-card__title"><i class="ti ti-settings-2" style="margin-right:8px;"></i>General Settings</span>
                </div>
                <div class="rmu-card__body">
                    <form method="POST" action="updateSettings.inc.php">
                        <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                        <input type="hidden" name="fuelComponentHidden" value="0">
                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                <input type="checkbox" name="fuelComponent" value="1"
                                       style="width:18px;height:18px;cursor:pointer;accent-color:var(--accent);"
                                       <?php echo $fuelComponent ? 'checked' : ''; ?>>
                                <span style="color:var(--txt-primary);font-size:.95rem;">
                                    Enable Fuel Component for Weekend &amp; Part-Time Lecturers
                                </span>
                            </label>
                            <button type="submit" class="rmu-btn rmu-btn--primary">
                                <i class="ti ti-device-floppy"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add New Bank -->
            <div class="rmu-card" style="margin-bottom:24px;">
                <div class="rmu-card__header">
                    <span class="rmu-card__title"><i class="ti ti-building-bank" style="margin-right:8px;"></i>Add New Bank</span>
                </div>
                <div class="rmu-card__body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:16px;align-items:end;">
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Bank Name <span class="required">*</span></label>
                                <input type="text" name="new_bank_name" class="rmu-input"
                                       placeholder="e.g. GCB BANK"
                                       oninput="this.value=this.value.toUpperCase()" required>
                            </div>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Branch Code <span class="required">*</span></label>
                                <input type="text" name="branch_code" class="rmu-input"
                                       placeholder="e.g. 030100"
                                       oninput="this.value=this.value.toUpperCase()" required>
                            </div>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Branch Name <span class="required">*</span></label>
                                <input type="text" name="branch_name" class="rmu-input"
                                       placeholder="e.g. TEMA MAIN"
                                       oninput="this.value=this.value.toUpperCase()" required>
                            </div>
                            <button type="submit" name="add_bank" class="rmu-btn rmu-btn--primary">
                                <i class="ti ti-plus"></i> Add
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bank / Branch Manager -->
            <div class="rmu-card" style="margin-bottom:24px;">
                <div class="rmu-card__header">
                    <span class="rmu-card__title"><i class="ti ti-list" style="margin-right:8px;"></i>Manage Bank Branches</span>
                </div>
                <div class="rmu-card__body">

                    <!-- Bank selector -->
                    <form method="GET" style="margin-bottom:20px;">
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <label class="rmu-label" style="margin-bottom:0;white-space:nowrap;">Select Bank:</label>
                            <select name="bank_name" class="rmu-select" style="max-width:320px;"
                                    onchange="this.form.submit()">
                                <option value="">— Choose a Bank —</option>
                                <?php foreach ($banks as $b): ?>
                                <option value="<?php echo h($b['bank_name']); ?>"
                                    <?php echo ($selected_bank === $b['bank_name']) ? 'selected' : ''; ?>>
                                    <?php echo h($b['bank_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>

                    <?php if ($selected_bank !== ''): ?>

                    <!-- Branches table -->
                    <?php if (!empty($branches)): ?>
                    <div class="rmu-table-wrap" style="margin-bottom:24px;">
                        <table class="rmu-table">
                            <thead>
                                <tr>
                                    <th>Branch Code</th>
                                    <th>Bank Name</th>
                                    <th>Branch</th>
                                    <th style="text-align:center;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($branches as $br): ?>
                                <tr>
                                    <td><?php echo h($br['branch_code']); ?></td>
                                    <td><?php echo h($br['bank_name']); ?></td>
                                    <td><?php echo h($br['bank_branch']); ?></td>
                                    <td style="text-align:center;">
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Remove this branch?');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?php echo h(csrf_token()); ?>">
                                            <input type="hidden" name="bank_branch_id"
                                                   value="<?php echo (int)$br['bank_branch_id']; ?>">
                                            <input type="hidden" name="bank_name"
                                                   value="<?php echo h($selected_bank); ?>">
                                            <button type="submit" name="remove_branch"
                                                    class="rmu-btn rmu-btn--danger"
                                                    style="padding:4px 10px;">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p style="color:var(--txt-muted);margin-bottom:20px;">No branches found for this bank.</p>
                    <?php endif; ?>

                    <!-- Add Branch -->
                    <div style="border-top:1px solid var(--divider);padding-top:20px;">
                        <div style="font-weight:600;color:var(--txt-primary);margin-bottom:14px;">
                            Add Branch to <?php echo h($selected_bank); ?>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                            <input type="hidden" name="bank_name" value="<?php echo h($selected_bank); ?>">
                            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:16px;align-items:end;">
                                <div class="rmu-form-group" style="margin-bottom:0;">
                                    <label class="rmu-label">Branch Code <span class="required">*</span></label>
                                    <input type="text" name="branch_code" class="rmu-input"
                                           placeholder="e.g. 030105"
                                           oninput="this.value=this.value.toUpperCase()" required>
                                </div>
                                <div class="rmu-form-group" style="margin-bottom:0;">
                                    <label class="rmu-label">Branch Name <span class="required">*</span></label>
                                    <input type="text" name="bank_branch" class="rmu-input"
                                           placeholder="e.g. TEMA WEST"
                                           oninput="this.value=this.value.toUpperCase()" required>
                                </div>
                                <button type="submit" name="add_branch" class="rmu-btn rmu-btn--primary">
                                    <i class="ti ti-plus"></i> Add Branch
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
