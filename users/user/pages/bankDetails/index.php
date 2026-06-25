<?php
$pageTitle = 'Bank Details';
include_once '../../assets/partials/_head.php';

$userId = current_user_id();

$stmt = mysqli_prepare($conn, 'SELECT * FROM user_bank_details WHERE userId = ? LIMIT 1');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $bank = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
} else {
    $bank = null;
}

$curBank   = $bank['bank_name']   ?? '';
$curBranch = $bank['bank_branch'] ?? '';

// Distinct banks for the dropdown.
$banks = [];
$bres = mysqli_query($conn,
    "SELECT DISTINCT bank_name FROM banks_branches
     WHERE bank_name IS NOT NULL AND bank_name <> '' ORDER BY bank_name");
while ($bres && $row = mysqli_fetch_row($bres)) { $banks[] = $row[0]; }
// Preserve any legacy free-text value not present in the master list.
if ($curBank !== '' && !in_array($curBank, $banks, true)) array_unshift($banks, $curBank);

// Branches for the currently-saved bank (so the page loads pre-populated).
$branches = [];
if ($curBank !== '') {
    $bs = mysqli_prepare($conn,
        "SELECT DISTINCT bank_branch FROM banks_branches
         WHERE bank_name = ? AND bank_branch IS NOT NULL AND bank_branch <> '' ORDER BY bank_branch");
    if ($bs) {
        mysqli_stmt_bind_param($bs, 's', $curBank);
        mysqli_stmt_execute($bs);
        $rs = mysqli_stmt_get_result($bs);
        while ($row = mysqli_fetch_row($rs)) { $branches[] = $row[0]; }
        mysqli_stmt_close($bs);
    }
    if ($curBranch !== '' && !in_array($curBranch, $branches, true)) array_unshift($branches, $curBranch);
}
?>
<body>
<div class="container-scroller">
    <?php include '../../assets/partials/_navbar.php'; ?>

    <div class="container-fluid page-body-wrapper">
        <?php include '../../assets/partials/_sidebar.php'; ?>

        <div class="main-panel">
            <div class="content-wrapper">

                <div class="rmu-page-header">
                    <div class="rmu-page-header__title">Bank Details</div>
                    <div class="rmu-page-header__sub">
                        Your bank information is used for payment processing once a claim is approved
                    </div>
                </div>

                <?php if (!$bank): ?>
                <div class="rmu-alert rmu-alert--warning" style="margin-bottom:20px;">
                    <i class="ti ti-alert-triangle"></i>
                    No bank details on file. Please fill in your details below so Finance can process your payments.
                </div>
                <?php endif; ?>

                <div class="rmu-card" style="max-width:640px;">
                    <div class="rmu-card__header">
                        <span class="rmu-card__title">
                            <i class="ti ti-building-bank" style="margin-right:6px;"></i>Payment Account
                        </span>
                        <?php if ($bank): ?>
                        <span class="rmu-badge rmu-badge--success">
                            <i class="ti ti-circle-check"></i> On file
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="rmu-card__body">
                        <form id="bankForm">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div class="rmu-form-group">
                                    <label class="rmu-label" for="bank_name">
                                        Bank Name <span class="required">*</span>
                                    </label>
                                    <select class="rmu-select" id="bank_name" name="bank_name" required
                                            onchange="onBankChange()">
                                        <option value="">— Select Bank —</option>
                                        <?php foreach ($banks as $b): ?>
                                        <option value="<?php echo h($b); ?>" <?php echo $b === $curBank ? 'selected' : ''; ?>>
                                            <?php echo h($b); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="rmu-form-group">
                                    <label class="rmu-label" for="bank_branch">Branch</label>
                                    <select class="rmu-select" id="bank_branch" name="bank_branch"
                                            <?php echo $curBank === '' ? 'disabled' : ''; ?>>
                                        <option value="">— <?php echo $curBank === '' ? 'Select a bank first' : 'Select Branch'; ?> —</option>
                                        <?php foreach ($branches as $br): ?>
                                        <option value="<?php echo h($br); ?>" <?php echo $br === $curBranch ? 'selected' : ''; ?>>
                                            <?php echo h($br); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="rmu-form-group">
                                    <label class="rmu-label" for="account_number">
                                        Account Number <span class="required">*</span>
                                    </label>
                                    <input type="text" class="rmu-input" id="account_number" name="account_number"
                                           placeholder="e.g. 1234567890"
                                           value="<?php echo h($bank['account_number'] ?? ''); ?>"
                                           maxlength="30" required>
                                </div>
                                <div class="rmu-form-group">
                                    <label class="rmu-label" for="account_name">
                                        Account Name <span class="required">*</span>
                                    </label>
                                    <input type="text" class="rmu-input" id="account_name" name="account_name"
                                           placeholder="Name as it appears on the account"
                                           value="<?php echo h($bank['account_name'] ?? ''); ?>"
                                           maxlength="120" required>
                                </div>
                            </div>

                            <div style="margin-top:20px;display:flex;gap:10px;align-items:center;">
                                <button type="button" class="rmu-btn rmu-btn--primary" id="saveBtn"
                                        onclick="saveBankDetails()">
                                    <i class="ti ti-device-floppy"></i> Save Bank Details
                                </button>
                                <span id="saveStatus" style="font-size:.8rem;color:var(--txt-muted);"></span>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <?php include '../../assets/partials/_footer.php'; ?>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
const CSRF = '<?php echo h(csrf_token()); ?>';

// Load branches for the selected bank (branches depend on the chosen bank).
function onBankChange() {
    const bank = document.getElementById('bank_name').value;
    const sel  = document.getElementById('bank_branch');
    sel.innerHTML = '<option value="">Loading…</option>';
    sel.disabled  = true;
    if (!bank) {
        sel.innerHTML = '<option value="">— Select a bank first —</option>';
        return;
    }
    fetch('fetchBranches.inc.php?bank_name=' + encodeURIComponent(bank))
        .then(r => r.json())
        .then(list => {
            if (!Array.isArray(list) || !list.length) {
                sel.innerHTML = '<option value="">— No branches found —</option>';
                sel.disabled  = true;
                return;
            }
            sel.innerHTML = '<option value="">— Select Branch —</option>';
            list.forEach(b => {
                const o = document.createElement('option');
                o.value = o.textContent = b;
                sel.appendChild(o);
            });
            sel.disabled = false;
        })
        .catch(() => {
            sel.innerHTML = '<option value="">Error loading branches</option>';
            sel.disabled  = false;
        });
}

function saveBankDetails() {
    const bank_name      = document.getElementById('bank_name').value.trim();
    const bank_branch    = document.getElementById('bank_branch').value.trim();
    const account_number = document.getElementById('account_number').value.trim();
    const account_name   = document.getElementById('account_name').value.trim();

    if (!bank_name || !account_number || !account_name) {
        Swal.fire({
            icon: 'error', title: 'Validation Error',
            text: 'Bank name, account number, and account name are required.',
            background: '#ffffff', color: '#0f2744', confirmButtonColor: '#1d4ed8',
        });
        return;
    }

    const btn    = document.getElementById('saveBtn');
    const status = document.getElementById('saveStatus');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader" style="animation:spin .8s linear infinite;"></i> Saving…';
    status.textContent = '';

    const fd = new FormData();
    fd.append('csrf_token',    CSRF);
    fd.append('bank_name',     bank_name);
    fd.append('bank_branch',   bank_branch);
    fd.append('account_number', account_number);
    fd.append('account_name',  account_name);

    fetch('saveBankDetails.inc.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                Swal.fire({
                    icon: 'success', title: 'Saved',
                    text: data.message || 'Bank details updated.',
                    background: '#ffffff', color: '#0f2744',
                    timer: 2200, showConfirmButton: false,
                });
                status.innerHTML = '<span style="color:#22c55e;">● Saved</span>';
            } else {
                Swal.fire({
                    icon: 'error', title: 'Save Failed',
                    text: data.message || 'Please try again.',
                    background: '#ffffff', color: '#0f2744', confirmButtonColor: '#1d4ed8',
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error', title: 'Network Error',
                text: 'Could not reach the server. Please try again.',
                background: '#ffffff', color: '#0f2744', confirmButtonColor: '#1d4ed8',
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy"></i> Save Bank Details';
        });
}
</script>
</body>
</html>
