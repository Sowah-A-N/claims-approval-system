<?php
$pageTitle = 'File New Claim';
include_once '../../assets/partials/_head.php';
require_once '../../queries/claim.queries.php';

$userId      = current_user_id();
$currentRate = isset($_SESSION['rate']) ? (float)$_SESSION['rate'] : 0;

$departmentResult = mysqli_query($conn, 'SELECT dept_name FROM department ORDER BY dept_name ASC');
$classList        = db_get_all_classes($conn);   // existing class codes for the dropdown

// Fetch fuel settings in one query
$fsq = mysqli_query($conn,
    "SELECT settingName, settingValue FROM settings WHERE settingName IN ('fuelComponent','fuelAmount')");
$fs = [];
while ($r = mysqli_fetch_assoc($fsq)) $fs[$r['settingName']] = $r['settingValue'];
$fuelEnabled = (int)($fs['fuelComponent'] ?? 0) === 1;
$fuelValue   = $fuelEnabled ? (float)($fs['fuelAmount'] ?? 0) : 0;

// Holiday dates the recurring-session generator should skip (#8).
$holidaysJson = json_encode(db_get_holiday_dates($conn));
if ($holidaysJson === false) $holidaysJson = '[]';

// Draft loading — populate if ?claimTempId= is set
$draft       = null;
$draftSlots  = [];
$claimTempId = isset($_GET['claimTempId']) ? (int)$_GET['claimTempId'] : 0;
if ($claimTempId > 0) {
    $draft = db_get_saved_claim_by_owner($conn, $claimTempId, $userId);
    if ($draft) {
        $rows    = db_get_claim_data_rows($conn, $claimTempId);
        $slotMap = [];
        foreach ($rows as $row) {
            $key = $row['start_time'] . '|' . $row['end_time'] . '|' . $row['periods'] . '|' . $row['fuelComponent'];
            if (!isset($slotMap[$key])) {
                $slotMap[$key] = [
                    'startTime'     => $row['start_time'],
                    'endTime'       => $row['end_time'],
                    'periods'       => (int)$row['periods'],
                    'subTotal'      => (float)$row['subTotal'],
                    'fuelComponent' => (int)$row['fuelComponent'],
                    'dates'         => [],
                ];
            }
            $slotMap[$key]['dates'][] = $row['date'];
        }
        $draftSlots = array_values($slotMap);
    } else {
        $claimTempId = 0; // not owned by this user
    }
}

$draftJson = json_encode($draft ? [
    'claimTempId' => $claimTempId,
    'department'  => $draft['department'],
    'programme'   => $draft['programme'],
    'course'      => $draft['course'],
    'class'       => $draft['class'] ?? '',
] : null);
if ($draftJson === false) $draftJson = 'null';

$draftSlotsJson = json_encode($draftSlots);
if ($draftSlotsJson === false) $draftSlotsJson = '[]';
?>
<body>
<div class="container-scroller">
    <?php include '../../assets/partials/_navbar.php'; ?>

    <div class="container-fluid page-body-wrapper">
        <?php include '../../assets/partials/_sidebar.php'; ?>

        <div class="main-panel">
            <div class="content-wrapper">

                <div class="rmu-page-header">
                    <div class="rmu-page-header__title">
                        <?php echo $claimTempId ? 'Edit Draft Claim' : 'File New Claim'; ?>
                    </div>
                    <div class="rmu-page-header__sub">
                        Select a course, add your teaching sessions with dates, then save as draft or submit
                    </div>
                </div>

                <!-- ── Claim Details ──────────────────────────────────────── -->
                <div class="rmu-card" style="margin-bottom:24px;">
                    <div class="rmu-card__header">
                        <span class="rmu-card__title">Claim Details</span>
                    </div>
                    <div class="rmu-card__body">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;">
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label" for="department">Department <span class="required">*</span></label>
                                <select class="rmu-select" id="department">
                                    <option value="">— Select Department —</option>
                                    <?php while ($r = mysqli_fetch_assoc($departmentResult)): ?>
                                    <option value="<?php echo h($r['dept_name']); ?>"><?php echo h($r['dept_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label" for="programme">Programme <span class="required">*</span></label>
                                <select class="rmu-select" id="programme" disabled>
                                    <option value="">— Select Department First —</option>
                                </select>
                            </div>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label" for="course">Course <span class="required">*</span></label>
                                <select class="rmu-select" id="course" disabled>
                                    <option value="">— Select Department First —</option>
                                </select>
                            </div>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label" for="class">Class <span class="required">*</span></label>
                                <input type="text" class="rmu-input" id="class" name="class"
                                       list="classList" maxlength="20" autocomplete="off"
                                       placeholder="e.g. BIT27" oninput="onClassInput(this)">
                                <datalist id="classList">
                                    <?php foreach ($classList as $c): ?>
                                    <option value="<?php echo h($c); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="rmu-form-hint">Pick an existing class or type a new one (e.g. BIT27).</div>
                            </div>
                        </div>
                        <div style="margin-top:16px;display:flex;align-items:center;gap:10px;">
                            <span class="rmu-label" style="margin-bottom:0;">Your Rate:</span>
                            <span class="rmu-badge rmu-badge--primary" style="font-size:.9rem;padding:5px 14px;">
                                GH₵ <?php echo h(number_format($currentRate, 2)); ?> / period
                            </span>
                            <input type="hidden" id="rate" value="<?php echo h($currentRate); ?>">
                        </div>
                    </div>
                </div>

                <!-- ── Teaching Sessions ──────────────────────────────────── -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <div>
                        <div style="font-size:1rem;font-weight:600;color:var(--txt-primary);">Teaching Sessions</div>
                        <div style="font-size:.78rem;color:var(--txt-muted);margin-top:3px;">
                            One entry per unique time slot. Periods and sub-total are calculated automatically.
                        </div>
                    </div>
                    <button type="button" class="rmu-btn rmu-btn--primary" onclick="addSlot()">
                        <i class="ti ti-plus"></i> Add Session
                    </button>
                </div>

                <div id="slotsContainer"></div>

                <div id="emptySlots" style="text-align:center;padding:36px 20px;color:var(--txt-muted);
                    background:var(--surface-2);border:1px dashed var(--divider);
                    border-radius:12px;margin-bottom:24px;">
                    <i class="ti ti-calendar-off" style="font-size:2.2rem;display:block;margin-bottom:10px;opacity:.5;"></i>
                    No sessions yet. Click <strong style="color:var(--txt-primary);">Add Session</strong> to begin.
                </div>

                <!-- ── Live Summary ───────────────────────────────────────── -->
                <div class="rmu-card" id="summaryCard" style="margin-bottom:24px;display:none;">
                    <div class="rmu-card__header">
                        <span class="rmu-card__title">Claim Summary</span>
                    </div>
                    <div class="rmu-card__body" style="padding-bottom:0;">
                        <div class="rmu-table-wrap">
                            <table class="rmu-table">
                                <thead>
                                    <tr>
                                        <th>Session</th>
                                        <th>Time Slot</th>
                                        <th>Periods</th>
                                        <th>Dates</th>
                                        <th>Per Session (GH₵)</th>
                                        <th>Session Total (GH₵)</th>
                                    </tr>
                                </thead>
                                <tbody id="summaryBody"></tbody>
                            </table>
                        </div>
                        <div style="display:flex;justify-content:flex-end;align-items:center;gap:16px;
                            padding:16px 0;border-top:1px solid var(--divider);margin-top:4px;">
                            <span style="color:var(--txt-secondary);font-size:.9rem;font-weight:500;">Grand Total</span>
                            <span style="font-size:1.3rem;font-weight:700;color:var(--txt-primary);">
                                GH₵ <span id="grandTotal">0.00</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ── Action Bar ─────────────────────────────────────────── -->
                <div style="display:flex;gap:12px;justify-content:flex-end;align-items:center;margin-bottom:40px;">
                    <span id="autoSaveStatus"
                          style="font-size:.78rem;color:var(--txt-muted);display:flex;
                                 align-items:center;gap:5px;margin-right:4px;">
                        ○ Not saved yet
                    </span>
                    <button type="button" class="rmu-btn rmu-btn--secondary" id="saveDraftBtn" onclick="saveDraft()">
                        <i class="ti ti-device-floppy"></i> Save Draft
                    </button>
                    <button type="button" class="rmu-btn rmu-btn--primary" id="submitClaimBtn" onclick="submitClaim()">
                        <i class="ti ti-send"></i> Submit Claim
                    </button>
                </div>

            </div>
            <?php include '../../assets/partials/_footer.php'; ?>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.slot-periods, .slot-subtotal { color: var(--txt-secondary) !important; }
.rmu-chip { display:inline-flex; align-items:center; gap:4px; background:var(--surface-2);
  border:1px solid var(--divider); border-radius:6px; padding:3px 4px 3px 10px;
  font-size:.82rem; color:var(--txt-primary); }
.rmu-chip__x { background:none; border:none; color:var(--txt-muted); cursor:pointer;
  font-size:1.05rem; line-height:1; padding:0 4px; border-radius:4px; }
.rmu-chip__x:hover { color:var(--clr-danger); }
.rmu-preview-session { padding:12px 0; border-bottom:1px solid var(--divider); }
.rmu-preview-session:last-child { border-bottom:none; }
</style>

<!-- Editable review / preview modal -->
<div class="rmu-modal-backdrop" id="previewBackdrop" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
  <div class="rmu-modal" style="max-width:640px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title" id="previewTitle"><i class="ti ti-eye"></i> Review Claim</span>
      <button class="rmu-modal__close" onclick="closePreview()" aria-label="Close review"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <p style="font-size:.8rem;color:var(--txt-muted);margin-bottom:12px;">
        Review your sessions below. Remove any date with its &times; before confirming.
      </p>
      <div id="previewMeta" style="margin-bottom:8px;font-size:.85rem;color:var(--txt-secondary);"></div>
      <div id="previewSessions"></div>
      <div style="display:flex;justify-content:flex-end;align-items:center;gap:14px;
                  padding-top:14px;border-top:1px solid var(--divider);margin-top:8px;">
        <span style="color:var(--txt-secondary);font-weight:500;">Grand Total</span>
        <span style="font-size:1.2rem;font-weight:700;color:var(--txt-primary);">GH&#8373; <span id="previewTotal">0.00</span></span>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
        <button type="button" class="rmu-btn rmu-btn--secondary" onclick="closePreview()">Cancel</button>
        <button type="button" class="rmu-btn rmu-btn--primary" id="previewConfirmBtn" onclick="confirmPreview()">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
const RATE         = <?php echo json_encode($currentRate); ?>;
const FUEL_ENABLED = <?php echo json_encode($fuelEnabled); ?>;
const FUEL_VALUE   = <?php echo json_encode($fuelValue); ?>;
const DRAFT        = <?php echo $draftJson; ?>;
const DRAFT_SLOTS  = <?php echo $draftSlotsJson; ?>;
const CSRF         = '<?php echo h(csrf_token()); ?>';
const HOLIDAYS     = new Set(<?php echo $holidaysJson; ?>);

let slotCounter          = 0;
let currentClaimTempId   = DRAFT ? DRAFT.claimTempId : 0;

// ── Utilities ─────────────────────────────────────────────────────────────────

function timeToMins(t) {
    if (!t) return 0;
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}

function fmt(n) { return Number(n).toFixed(2); }

function swal(icon, title, text) {
    Swal.fire({
        icon, title, text,
        background: '#ffffff',
        color: '#0f2744',
        confirmButtonColor: '#1d4ed8'
    });
}

function swalSuccess(text) {
    Swal.fire({
        icon: 'success', title: 'Done', text,
        background: '#ffffff', color: '#0f2744',
        timer: 2500, showConfirmButton: false
    });
}

function setBusy(btnId, busy, label) {
    const btn = document.getElementById(btnId);
    btn.disabled = busy;
    btn.innerHTML = busy
        ? `<i class="ti ti-loader" style="animation:spin .8s linear infinite;"></i> ${label}`
        : label;
}

// ── Slot management ───────────────────────────────────────────────────────────

function addSlot(prefill) {
    slotCounter++;
    const n    = slotCounter;
    const card = document.createElement('div');
    card.className = 'rmu-card rmu-slot-card';
    card.style.marginBottom = '16px';

    card.innerHTML = `
        <div class="rmu-card__header"
             style="display:flex;justify-content:space-between;align-items:center;">
            <span class="rmu-card__title slot-title">Session ${n}</span>
            <button type="button"
                    class="rmu-btn rmu-btn--danger"
                    style="padding:4px 10px;font-size:.78rem;"
                    onclick="removeSlot(this)">
                <i class="ti ti-x"></i> Remove
            </button>
        </div>
        <div class="rmu-card__body">

            <!-- Time / periods / sub-total row -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px;">
                <div class="rmu-form-group" style="margin-bottom:0;">
                    <label class="rmu-label">Start Time <span class="required">*</span></label>
                    <input type="time" class="rmu-input slot-start" oninput="recalculate()">
                </div>
                <div class="rmu-form-group" style="margin-bottom:0;">
                    <label class="rmu-label">End Time <span class="required">*</span></label>
                    <input type="time" class="rmu-input slot-end" oninput="recalculate()">
                </div>
                <div class="rmu-form-group" style="margin-bottom:0;">
                    <label class="rmu-label">Periods</label>
                    <input type="text" class="rmu-input slot-periods"
                           readonly placeholder="— auto —" tabindex="-1">
                </div>
                <div class="rmu-form-group" style="margin-bottom:0;">
                    <label class="rmu-label">Per Session (GH₵)</label>
                    <input type="text" class="rmu-input slot-subtotal"
                           readonly placeholder="— auto —" tabindex="-1">
                </div>
            </div>

            ${FUEL_ENABLED ? `
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <input type="checkbox" class="slot-fuel" id="fuel-${n}"
                       onchange="recalculate()"
                       style="width:16px;height:16px;accent-color:var(--clr-primary);cursor:pointer;flex-shrink:0;">
                <label for="fuel-${n}" class="rmu-label"
                       style="margin-bottom:0;cursor:pointer;font-size:.82rem;">
                    Include Fuel Component
                    <span style="color:var(--txt-muted);">(+GH₵ ${fmt(FUEL_VALUE)} per session)</span>
                </label>
            </div>` : ''}

            <div style="border-top:1px solid var(--divider);margin-bottom:14px;"></div>

            <!-- Recurring-date generator -->
            <div class="recur-panel" style="background:var(--surface-2);border:1px solid var(--divider);border-radius:8px;padding:12px;margin-bottom:14px;">
                <div style="font-size:.74rem;color:var(--txt-muted);margin-bottom:8px;">
                    <i class="ti ti-repeat"></i> Generate recurring dates (weekly) &mdash; skips public holidays
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                    <div class="rmu-form-group" style="margin-bottom:0;">
                        <label class="rmu-label" style="font-size:.72rem;">From</label>
                        <input type="date" class="rmu-input recur-from" style="width:150px;">
                    </div>
                    <div class="rmu-form-group" style="margin-bottom:0;">
                        <label class="rmu-label" style="font-size:.72rem;">To</label>
                        <input type="date" class="rmu-input recur-to" style="width:150px;">
                    </div>
                    <div class="recur-days" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;font-size:.8rem;">
                        <label style="display:flex;gap:3px;align-items:center;cursor:pointer;"><input type="checkbox" class="recur-day" value="1">Mon</label>
                        <label style="display:flex;gap:3px;align-items:center;cursor:pointer;"><input type="checkbox" class="recur-day" value="2">Tue</label>
                        <label style="display:flex;gap:3px;align-items:center;cursor:pointer;"><input type="checkbox" class="recur-day" value="3">Wed</label>
                        <label style="display:flex;gap:3px;align-items:center;cursor:pointer;"><input type="checkbox" class="recur-day" value="4">Thu</label>
                        <label style="display:flex;gap:3px;align-items:center;cursor:pointer;"><input type="checkbox" class="recur-day" value="5">Fri</label>
                        <label style="display:flex;gap:3px;align-items:center;cursor:pointer;"><input type="checkbox" class="recur-day" value="6">Sat</label>
                        <label style="display:flex;gap:3px;align-items:center;cursor:pointer;"><input type="checkbox" class="recur-day" value="0">Sun</label>
                    </div>
                    <button type="button" class="rmu-btn rmu-btn--secondary" style="padding:5px 12px;font-size:.78rem;"
                            onclick="generateRecurring(this)">
                        <i class="ti ti-calendar-plus"></i> Generate
                    </button>
                </div>
            </div>

            <!-- Dates -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <label class="rmu-label" style="margin-bottom:0;">
                    Teaching Dates <span class="required">*</span>
                </label>
                <button type="button"
                        class="rmu-btn rmu-btn--secondary"
                        style="padding:5px 12px;font-size:.78rem;"
                        onclick="addDate(this)">
                    <i class="ti ti-calendar-plus"></i> Add Date
                </button>
            </div>
            <div class="slot-dates" style="display:flex;flex-wrap:wrap;gap:8px;min-height:32px;"></div>
            <div class="slot-dates-empty"
                 style="color:var(--txt-muted);font-size:.78rem;margin-top:6px;">
                No dates added yet.
            </div>
        </div>`;

    document.getElementById('slotsContainer').appendChild(card);
    document.getElementById('emptySlots').style.display  = 'none';
    document.getElementById('summaryCard').style.display = '';

    if (prefill) {
        card.querySelector('.slot-start').value = prefill.startTime || '';
        card.querySelector('.slot-end').value   = prefill.endTime   || '';
        const fc = card.querySelector('.slot-fuel');
        if (fc) fc.checked = !!prefill.fuelComponent;
        (prefill.dates || []).forEach(d => addDateToCard(card, d));
    }

    recalculate();
    return card;
}

function removeSlot(btn) {
    btn.closest('.rmu-slot-card').remove();
    renumberSlots();
    recalculate();
    if (!document.querySelector('.rmu-slot-card')) {
        document.getElementById('emptySlots').style.display  = '';
        document.getElementById('summaryCard').style.display = 'none';
    }
}

function renumberSlots() {
    document.querySelectorAll('.rmu-slot-card').forEach((c, i) => {
        c.querySelector('.slot-title').textContent = 'Session ' + (i + 1);
    });
}

// ── Date management ───────────────────────────────────────────────────────────

function addDateToCard(card, val) {
    const container = card.querySelector('.slot-dates');
    const empty     = card.querySelector('.slot-dates-empty');

    const pill = document.createElement('div');
    pill.className  = 'date-pill';
    pill.style.cssText =
        'display:flex;align-items:center;gap:4px;' +
        'background:var(--surface-2);border:1px solid var(--divider);' +
        'border-radius:6px;padding:3px 6px 3px 10px;';
    pill.innerHTML = `
        <input type="date" class="slot-date" value="${val || ''}"
               style="background:transparent;border:none;color:var(--txt-primary);
                      font-size:.85rem;outline:none;width:130px;cursor:pointer;"
               onchange="recalculate()">
        <button type="button" onclick="removeDate(this)"
                style="background:none;border:none;color:var(--txt-muted);
                       cursor:pointer;padding:2px 4px;line-height:1;
                       display:flex;align-items:center;"
                title="Remove date">
            <i class="ti ti-x" style="font-size:.75rem;"></i>
        </button>`;

    container.appendChild(pill);
    if (empty) empty.style.display = 'none';
}

function addDate(btn) {
    const card = btn.closest('.rmu-slot-card');
    addDateToCard(card, '');
    card.querySelector('.slot-dates').lastElementChild.querySelector('.slot-date').focus();
    recalculate();
}

function removeDate(btn) {
    const pill  = btn.closest('.date-pill');
    const card  = pill.closest('.rmu-slot-card');
    const empty = card.querySelector('.slot-dates-empty');
    pill.remove();
    if (!card.querySelectorAll('.slot-date').length && empty) empty.style.display = '';
    recalculate();
}

// ── Recurring-date generator (#6) ───────────────────────────────────────────────

function _pad2(n) { return (n < 10 ? '0' : '') + n; }
function _fmtDate(d) { return d.getFullYear() + '-' + _pad2(d.getMonth() + 1) + '-' + _pad2(d.getDate()); }

function generateRecurring(btn) {
    const card  = btn.closest('.rmu-slot-card');
    const fromV = card.querySelector('.recur-from').value;
    const toV   = card.querySelector('.recur-to').value;
    const days  = Array.from(card.querySelectorAll('.recur-day:checked'))
                       .map(function(c) { return parseInt(c.value, 10); });

    if (!fromV || !toV) { swal('error', 'Missing Range', 'Please choose both a From and To date.'); return; }
    if (!days.length)   { swal('error', 'No Weekdays', 'Please select at least one weekday.'); return; }

    const from = new Date(fromV + 'T00:00:00');
    const to   = new Date(toV   + 'T00:00:00');
    if (from > to) { swal('error', 'Invalid Range', 'The From date must be on or before the To date.'); return; }

    const existing = new Set(
        Array.from(card.querySelectorAll('.slot-date'))
             .map(function(i) { return i.value; })
             .filter(Boolean));

    const daySet = new Set(days);
    let added = 0, skippedHoliday = 0, skippedDup = 0, capped = false;

    for (let d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) {
        if (!daySet.has(d.getDay())) continue;
        const ds = _fmtDate(d);
        if (HOLIDAYS.has(ds))   { skippedHoliday++; continue; }
        if (existing.has(ds))   { skippedDup++;     continue; }
        if (existing.size + added >= 365) { capped = true; break; }
        addDateToCard(card, ds);
        existing.add(ds);
        added++;
    }
    recalculate();

    let msg = added + ' date(s) added.';
    if (skippedHoliday) msg += ' ' + skippedHoliday + ' holiday(s) skipped.';
    if (skippedDup)     msg += ' ' + skippedDup + ' duplicate(s) skipped.';
    if (capped)         msg += ' Stopped at the 365-date limit.';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: added ? 'success' : 'info',
            title: added ? 'Dates Generated' : 'Nothing Added',
            text: msg, background: '#ffffff', color: '#0f2744',
            timer: 2800, showConfirmButton: false,
        });
    } else {
        alert(msg);
    }
}

// ── Calculation ───────────────────────────────────────────────────────────────

function recalculate() {
    let grandTotal = 0;
    const rows = [];

    document.querySelectorAll('.rmu-slot-card').forEach((card, i) => {
        const start    = card.querySelector('.slot-start').value;
        const end      = card.querySelector('.slot-end').value;
        const fuelChk  = card.querySelector('.slot-fuel');
        const hasFuel  = fuelChk && fuelChk.checked;
        const filled   = Array.from(card.querySelectorAll('.slot-date'))
                              .filter(d => d.value).length;

        let periods = 0, perSession = 0;
        if (start && end) {
            const diff = timeToMins(end) - timeToMins(start);
            if (diff > 0) {
                periods    = Math.ceil(diff / 50);
                perSession = periods * RATE + (hasFuel ? FUEL_VALUE : 0);
            }
        }

        card.querySelector('.slot-periods').value  = periods    > 0 ? periods          : '';
        card.querySelector('.slot-subtotal').value = perSession > 0 ? fmt(perSession)   : '';

        const sessionTotal = perSession * filled;
        grandTotal += sessionTotal;
        rows.push({ n: i + 1, start, end, periods, perSession, filled, sessionTotal });
    });

    updateSummary(rows, grandTotal);
}

function updateSummary(rows, grandTotal) {
    const tbody = document.getElementById('summaryBody');

    if (!rows.length) { tbody.innerHTML = ''; return; }

    tbody.innerHTML = rows.map(r => `
        <tr>
            <td>Session ${r.n}</td>
            <td>${r.start || '—'} → ${r.end || '—'}</td>
            <td>${r.periods || '—'}</td>
            <td>${r.filled}</td>
            <td>${r.perSession > 0 ? fmt(r.perSession) : '—'}</td>
            <td><strong>${fmt(r.sessionTotal)}</strong></td>
        </tr>`).join('');

    document.getElementById('grandTotal').textContent = fmt(grandTotal);
}

// ── Course AJAX ───────────────────────────────────────────────────────────────

function loadCourses(department, callback) {
    const sel = document.getElementById('course');
    sel.innerHTML = '<option value="">Loading…</option>';
    sel.disabled  = true;
    if (!department) {
        sel.innerHTML = '<option value="">— Select Department First —</option>';
        return;
    }
    fetch(`getCourses.php?department=${encodeURIComponent(department)}`)
        .then(r => r.json())
        .then(courses => {
            if (!Array.isArray(courses) || courses.length === 0) {
                sel.innerHTML = '<option value="">— No courses for this department —</option>';
                sel.disabled = true;
            } else {
                sel.innerHTML = '<option value="">— Select Course —</option>';
                courses.forEach(c => {
                    const o = document.createElement('option');
                    o.value = o.textContent = c.name;
                    sel.appendChild(o);
                });
                sel.disabled = false;
            }
            if (callback) callback();
        })
        .catch(() => {
            sel.innerHTML = '<option value="">Error loading courses</option>';
            sel.disabled  = false;
        });
}

// Programmes depend on the selected department (programme.fk_department).
function loadProgrammes(department, callback) {
    const sel = document.getElementById('programme');
    sel.innerHTML = '<option value="">Loading…</option>';
    sel.disabled  = true;
    if (!department) {
        sel.innerHTML = '<option value="">— Select Department First —</option>';
        return;
    }
    fetch(`getProgrammes.php?department=${encodeURIComponent(department)}`)
        .then(r => r.json())
        .then(progs => {
            if (!progs.length) {
                sel.innerHTML = '<option value="">— No programmes for this department —</option>';
                sel.disabled = true;
            } else {
                sel.innerHTML = '<option value="">— Select Programme —</option>';
                progs.forEach(p => {
                    const o = document.createElement('option');
                    o.value = o.textContent = p.name;
                    sel.appendChild(o);
                });
                sel.disabled = false;
            }
            if (callback) callback();
        })
        .catch(() => {
            sel.innerHTML = '<option value="">Error loading programmes</option>';
            sel.disabled  = false;
        });
}

// Force the class code to upper-case as the user types (bit27 -> BIT27).
function onClassInput(el) {
    const pos = el.selectionStart;
    el.value = el.value.toUpperCase();
    try { el.setSelectionRange(pos, pos); } catch (e) {}
    if (typeof markDirty === 'function') markDirty();
}

document.getElementById('department').addEventListener('change', function () {
    loadProgrammes(this.value);
    loadCourses(this.value);
});

// ── Form payload builder ──────────────────────────────────────────────────────

function buildPayload() {
    const dept   = document.getElementById('department').value.trim();
    const prog   = document.getElementById('programme').value.trim();
    const course = document.getElementById('course').value.trim();
    const cls    = document.getElementById('class').value.trim().toUpperCase();

    if (!dept || !prog || !course) {
        swal('error', 'Validation Error', 'Please select Department, Programme, and Course.');
        return null;
    }
    if (!cls) {
        swal('error', 'Validation Error', 'Please enter the Class (e.g. BIT27).');
        return null;
    }

    const slotCards = Array.from(document.querySelectorAll('.rmu-slot-card'));
    if (!slotCards.length) {
        swal('error', 'Validation Error', 'Please add at least one teaching session.');
        return null;
    }

    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('department', dept);
    fd.append('programme',  prog);
    fd.append('course',     course);
    fd.append('class',      cls);
    // rate is looked up server-side from the database; do not send it.

    for (let i = 0; i < slotCards.length; i++) {
        const card      = slotCards[i];
        const start     = card.querySelector('.slot-start').value;
        const end       = card.querySelector('.slot-end').value;
        const fuelChk   = card.querySelector('.slot-fuel');
        const fuel      = fuelChk && fuelChk.checked ? 1 : 0;
        const periods   = parseInt(card.querySelector('.slot-periods').value)   || 0;
        const subTotal  = parseFloat(card.querySelector('.slot-subtotal').value) || 0;
        const dates     = Array.from(card.querySelectorAll('.slot-date'))
                               .map(d => d.value).filter(d => d);

        if (!start || !end) {
            swal('error', 'Validation Error', `Session ${i + 1}: start and end time are required.`);
            return null;
        }
        if (timeToMins(end) <= timeToMins(start)) {
            swal('error', 'Validation Error', `Session ${i + 1}: end time must be after start time.`);
            return null;
        }
        if (!dates.length) {
            swal('error', 'Validation Error', `Session ${i + 1}: add at least one teaching date.`);
            return null;
        }

        fd.append(`timeSlots[${i}][startTime]`,     start);
        fd.append(`timeSlots[${i}][endTime]`,       end);
        fd.append(`timeSlots[${i}][periods]`,       periods);
        fd.append(`timeSlots[${i}][subTotal]`,      subTotal);
        fd.append(`timeSlots[${i}][fuelComponent]`, fuel);
        dates.forEach((d, di) => fd.append(`timeSlots[${i}][dates][${di}]`, d));
    }

    return fd;
}

// ── Submit ────────────────────────────────────────────────────────────────────

// Submit / Save Draft now route through an editable review modal first, so the
// claimant can verify every session and remove individual dates before the
// claim is sent or saved.
function submitClaim() { openPreview('submit'); }

function doSubmit() {
    const payload = buildPayload();
    if (!payload) return;

    setBusy('submitClaimBtn', true, 'Submitting…');

    fetch('multiClaimsSubmit.inc.php', { method: 'POST', body: payload })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                swalSuccess(data.message || 'Claim submitted successfully.');
                setTimeout(() => { window.location.href = '../myClaims/'; }, 2600);
            } else {
                swal('error', 'Submission Failed', data.message || data.error || 'Please try again.');
                setBusy('submitClaimBtn', false, '<i class="ti ti-send"></i> Submit Claim');
            }
        })
        .catch(() => swal('error', 'Network Error', 'Could not reach the server. Please try again.'))
        .finally(() => setBusy('submitClaimBtn', false, '<i class="ti ti-send"></i> Submit Claim'));
}

// ── Editable preview / review modal ─────────────────────────────────────────────

let _previewMode = null, _previewTrigger = null;

function _esc(s) {
    return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

function openPreview(mode) {
    const dept   = document.getElementById('department').value.trim();
    const prog   = document.getElementById('programme').value.trim();
    const course = document.getElementById('course').value.trim();
    const cls    = document.getElementById('class').value.trim().toUpperCase();
    if (!dept || !prog || !course) {
        swal('error', 'Validation Error', 'Please select Department, Programme, and Course.');
        return;
    }
    if (!cls) {
        swal('error', 'Validation Error', 'Please enter the Class (e.g. BIT27).');
        return;
    }
    const cards = Array.from(document.querySelectorAll('.rmu-slot-card'));
    if (!cards.length) {
        swal('error', 'Validation Error', 'Please add at least one teaching session.');
        return;
    }
    for (let i = 0; i < cards.length; i++) {
        const s = cards[i].querySelector('.slot-start').value;
        const e = cards[i].querySelector('.slot-end').value;
        if (!s || !e) {
            swal('error', 'Validation Error', `Session ${i + 1}: start and end time are required.`);
            return;
        }
        if (timeToMins(e) <= timeToMins(s)) {
            swal('error', 'Validation Error', `Session ${i + 1}: end time must be after start time.`);
            return;
        }
    }

    _previewMode    = mode;
    _previewTrigger = document.activeElement;
    document.getElementById('previewTitle').innerHTML = (mode === 'submit')
        ? '<i class="ti ti-eye"></i> Review &amp; Submit Claim'
        : '<i class="ti ti-eye"></i> Review &amp; Save Draft';
    const btn = document.getElementById('previewConfirmBtn');
    btn.innerHTML = (mode === 'submit')
        ? '<i class="ti ti-send"></i> Confirm &amp; Submit'
        : '<i class="ti ti-device-floppy"></i> Confirm &amp; Save Draft';

    renderPreview();

    const bd = document.getElementById('previewBackdrop');
    bd.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => btn.focus(), 60);
}

// Format 'YYYY-MM-DD' as 'Mon 13/07/2026' (day name + dd/mm/yyyy).
function fmtDayDMY(ds) {
    const d = new Date(ds + 'T00:00:00');
    if (isNaN(d.getTime())) return ds;
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    return days[d.getDay()] + ' ' + _pad2(d.getDate()) + '/' + _pad2(d.getMonth() + 1) + '/' + d.getFullYear();
}

function renderPreview() {
    const cards     = Array.from(document.querySelectorAll('.rmu-slot-card'));
    const dept      = document.getElementById('department').value;
    const prog      = document.getElementById('programme').value;
    const course    = document.getElementById('course').value;
    const cls       = document.getElementById('class').value.trim().toUpperCase();
    const showFuel  = !!FUEL_ENABLED;

    document.getElementById('previewMeta').innerHTML =
        '<strong style="color:var(--txt-primary);">' + _esc(cls) + '</strong> &middot; ' +
        _esc(course) + ' &middot; ' + _esc(prog) + ' &middot; ' + _esc(dept) +
        ' &middot; Rate GH&#8373; ' + fmt(RATE) + ' / period';

    let grand = 0, totalDates = 0, anyEmpty = false, rows = '', rowNum = 0;

    cards.forEach((card, i) => {
        const start      = card.querySelector('.slot-start').value || '—';
        const end        = card.querySelector('.slot-end').value || '—';
        const periods    = parseInt(card.querySelector('.slot-periods').value) || 0;
        const perSession = parseFloat(card.querySelector('.slot-subtotal').value) || 0;
        const fuelChk    = card.querySelector('.slot-fuel');
        const hasFuel    = FUEL_ENABLED && fuelChk && fuelChk.checked;
        const dates      = Array.from(card.querySelectorAll('.slot-date')).map(d => d.value).filter(Boolean);

        totalDates += dates.length;
        if (!dates.length) {
            anyEmpty = true;
            rows += '<tr><td>—</td>' +
                '<td colspan="' + (showFuel ? 5 : 4) + '" style="color:var(--clr-danger);font-size:.8rem;">' +
                'No dates — add some, or cancel and remove this session.</td><td></td></tr>';
            return;
        }
        dates.forEach(d => {
            grand += perSession;
            rows += '<tr>' +
                '<td>' + (++rowNum) + '</td>' +
                '<td style="white-space:nowrap;">' + _esc(fmtDayDMY(d)) + '</td>' +
                '<td style="white-space:nowrap;">' + _esc(start) + '–' + _esc(end) + '</td>' +
                '<td>' + periods + '</td>' +
                (showFuel ? ('<td>' + (hasFuel ? '<span class="rmu-badge rmu-badge--primary">Yes</span>' : '<span style="color:var(--txt-muted);">—</span>') + '</td>') : '') +
                '<td>' + fmt(perSession) + '</td>' +
                '<td style="text-align:center;"><button type="button" class="rmu-chip__x" ' +
                  'aria-label="Remove date ' + _esc(d) + '" title="Remove date" ' +
                  'onclick="removePreviewDate(' + i + ',&quot;' + _esc(d) + '&quot;)">&times;</button></td>' +
            '</tr>';
        });
    });

    document.getElementById('previewSessions').innerHTML =
        '<div class="rmu-table-wrap"><table class="rmu-table" style="margin:0;"><thead><tr>' +
        '<th>#</th><th>Date <span style="font-weight:400;text-transform:none;color:var(--txt-muted);">(dd/mm/yyyy)</span></th><th>Time</th><th>Periods</th>' +
        (showFuel ? '<th>Fuel</th>' : '') +
        '<th>Amount (GH&#8373;)</th><th></th>' +
        '</tr></thead><tbody>' + rows + '</tbody></table></div>';

    document.getElementById('previewTotal').textContent = fmt(grand);

    const btn = document.getElementById('previewConfirmBtn');
    btn.disabled      = anyEmpty || totalDates === 0;
    btn.style.opacity = btn.disabled ? '0.5' : '';
    btn.style.cursor  = btn.disabled ? 'not-allowed' : 'pointer';
}

function removePreviewDate(i, val) {
    const card = document.querySelectorAll('.rmu-slot-card')[i];
    if (!card) return;
    const input = Array.from(card.querySelectorAll('.slot-date')).find(d => d.value === val);
    if (input) {
        const pill = input.closest('.date-pill');
        if (pill) pill.remove();
    }
    const empty = card.querySelector('.slot-dates-empty');
    if (empty && !card.querySelectorAll('.slot-date').length) empty.style.display = '';
    recalculate();
    renderPreview();
}

function closePreview() {
    document.getElementById('previewBackdrop').classList.remove('open');
    document.body.style.overflow = '';
    if (_previewTrigger && _previewTrigger.focus) _previewTrigger.focus();
}

function confirmPreview() {
    const mode = _previewMode;
    closePreview();
    if (mode === 'submit') doSubmit();
    else doSaveDraft();
}

document.getElementById('previewBackdrop').addEventListener('click', function (e) {
    if (e.target === this) closePreview();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('previewBackdrop').classList.contains('open')) {
        closePreview();
    }
});

// ── Save Draft ────────────────────────────────────────────────────────────────

function saveDraft() { openPreview('draft'); }

function doSaveDraft() {
    const payload = buildPayload();
    if (!payload) return;

    if (currentClaimTempId) payload.append('claimTempId', currentClaimTempId);

    setBusy('saveDraftBtn', true, 'Saving…');

    fetch('saveDraft.inc.php', { method: 'POST', body: payload })
        .then(r => r.json())
        .then(data => {
            if (data.claimTempId) {
                currentClaimTempId = data.claimTempId;
                history.replaceState({}, '', `?claimTempId=${data.claimTempId}`);
                _autoSaveDirty = false;
                setAutoSaveStatus('saved');
                swalSuccess('Draft saved. You can return to it from My Claims.');
            } else {
                swal('error', 'Save Failed', data.error || 'Please try again.');
            }
        })
        .catch(() => swal('error', 'Network Error', 'Could not reach the server. Please try again.'))
        .finally(() => setBusy('saveDraftBtn', false,
            '<i class="ti ti-device-floppy"></i> Save Draft'));
}

// ── Auto-save ─────────────────────────────────────────────────────────────────

let _autoSaveDirty    = false;
let _autoSaveInFlight = false;
let _autoSaveTimer    = null;

function setAutoSaveStatus(state) {
    const el = document.getElementById('autoSaveStatus');
    if (!el) return;
    const cfg = {
        init:    { dot: '○', text: 'Not saved yet',      color: 'var(--txt-muted)' },
        unsaved: { dot: '○', text: 'Unsaved changes',    color: '#f59e0b' },
        saving:  { dot: '◌', text: 'Saving…',           color: 'var(--txt-muted)' },
        saved:   { dot: '●', text: 'Draft saved',        color: '#22c55e' },
        error:   { dot: '●', text: 'Auto-save failed',   color: '#ef4444' },
    };
    const c = cfg[state] || cfg.init;
    el.innerHTML = `<span style="color:${c.color};">${c.dot}</span> ${c.text}`;
}

function markDirty() {
    _autoSaveDirty = true;
    setAutoSaveStatus('unsaved');
    clearTimeout(_autoSaveTimer);
    _autoSaveTimer = setTimeout(autoSaveSilent, 3000);
}

function autoSaveSilent() {
    if (_autoSaveInFlight || !_autoSaveDirty) return;

    const dept   = document.getElementById('department').value.trim();
    const prog   = document.getElementById('programme').value.trim();
    const course = document.getElementById('course').value.trim();
    if (!dept || !prog || !course) return;

    const slotCards = Array.from(document.querySelectorAll('.rmu-slot-card'));
    if (!slotCards.length) return;

    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('department', dept);
    fd.append('programme',  prog);
    fd.append('course',     course);
    if (currentClaimTempId) fd.append('claimTempId', currentClaimTempId);

    for (let i = 0; i < slotCards.length; i++) {
        const card  = slotCards[i];
        const start = card.querySelector('.slot-start').value;
        const end   = card.querySelector('.slot-end').value;
        if (!start || !end) return; // slot not ready yet
        const fuelChk = card.querySelector('.slot-fuel');
        const fuel    = fuelChk && fuelChk.checked ? 1 : 0;
        const periods = parseInt(card.querySelector('.slot-periods').value)    || 0;
        const sub     = parseFloat(card.querySelector('.slot-subtotal').value) || 0;
        const dates   = Array.from(card.querySelectorAll('.slot-date'))
                             .map(d => d.value).filter(d => d);
        fd.append(`timeSlots[${i}][startTime]`,     start);
        fd.append(`timeSlots[${i}][endTime]`,       end);
        fd.append(`timeSlots[${i}][periods]`,       periods);
        fd.append(`timeSlots[${i}][subTotal]`,      sub);
        fd.append(`timeSlots[${i}][fuelComponent]`, fuel);
        dates.forEach((d, di) => fd.append(`timeSlots[${i}][dates][${di}]`, d));
    }

    _autoSaveInFlight = true;
    setAutoSaveStatus('saving');

    fetch('saveDraft.inc.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.claimTempId) {
                currentClaimTempId = data.claimTempId;
                history.replaceState({}, '', `?claimTempId=${data.claimTempId}`);
                _autoSaveDirty = false;
                setAutoSaveStatus('saved');
            } else {
                setAutoSaveStatus('error');
            }
        })
        .catch(() => setAutoSaveStatus('error'))
        .finally(() => { _autoSaveInFlight = false; });
}

// Periodic backup save every 60 s
setInterval(() => { if (_autoSaveDirty) autoSaveSilent(); }, 60000);

function observeFormChanges() {
    ['department', 'programme', 'course'].forEach(id => {
        document.getElementById(id).addEventListener('change', markDirty);
    });
    const sc = document.getElementById('slotsContainer');
    sc.addEventListener('change', markDirty);
    sc.addEventListener('input',  markDirty);
    // Catch slot additions / removals via MutationObserver
    new MutationObserver(markDirty).observe(sc, { childList: true });
}


// ── Draft pre-population ──────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    observeFormChanges();
    setAutoSaveStatus(currentClaimTempId ? 'saved' : 'init');

    if (!DRAFT) return;

    document.getElementById('department').value = DRAFT.department;
    if (DRAFT.class) document.getElementById('class').value = DRAFT.class;

    // Programmes load by department; set the saved value once options arrive.
    loadProgrammes(DRAFT.department, function () {
        document.getElementById('programme').value = DRAFT.programme;
    });

    loadCourses(DRAFT.department, function () {
        document.getElementById('course').value = DRAFT.course;
        DRAFT_SLOTS.forEach(slot => addSlot(slot));
        recalculate();
    });
});
</script>
</body>
