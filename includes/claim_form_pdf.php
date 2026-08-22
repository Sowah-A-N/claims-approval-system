<?php
/*
 * Shared renderer for the RMU claim form PDF.
 *
 * Produces an EXACT replica of the scanned physical form
 * "APPLICATION FOR PAYMENT OF LECTURING FEES TO RESOURCE PERSON"
 * (Doc. No. ACA/F/10) so finance/QA can compare it 1:1 with the paper form.
 * Used by both the claimant and finance downloads.
 *
 * $rows is the claim's teaching-session rows (as returned by
 * db_get_claim_download_data / the finance query) sharing one header. Expected
 * keys per row: claimId, first_name, last_name, other_names, user_department,
 * rate, programme, course, class, claim_date, start_time, end_time, periods.
 */

require_once __DIR__ . '/functions.php';

/* Convert a non-negative integer (< 1e12) to English words. */
function _claim_int_to_words($n) {
    $n = (int) $n;
    if ($n === 0) return 'zero';
    $ones = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
                  'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
                  'seventeen', 'eighteen', 'nineteen');
    $tens = array('', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety');

    $below_thousand = function ($num) use ($ones, $tens) {
        $str = '';
        if ($num >= 100) {
            $str .= $ones[intdiv($num, 100)] . ' hundred';
            $num %= 100;
            if ($num) $str .= ' and ';
        }
        if ($num >= 20) {
            $str .= $tens[intdiv($num, 10)];
            if ($num % 10) $str .= '-' . $ones[$num % 10];
        } elseif ($num > 0) {
            $str .= $ones[$num];
        }
        return $str;
    };

    $scales = array(1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand');
    $words = '';
    foreach ($scales as $value => $name) {
        if ($n >= $value) {
            $words .= $below_thousand(intdiv($n, $value)) . ' ' . $name;
            $n %= $value;
            if ($n) $words .= ', ';
        }
    }
    if ($n > 0) $words .= $below_thousand($n);
    return trim($words);
}

/*
 * Amount in words for GH¢ (cedis + pesewas), e.g.
 *   964     -> "Nine hundred and sixty-four Ghana cedis"
 *   1200.50 -> "One thousand, two hundred Ghana cedis and fifty pesewas"
 */
function claim_amount_in_words($amount) {
    $amount  = round((float) $amount, 2);
    $cedis   = (int) floor($amount);
    $pesewas = (int) round(($amount - $cedis) * 100);

    $words = ucfirst(_claim_int_to_words($cedis)) . ' Ghana cedi' . ($cedis === 1 ? '' : 's');
    if ($pesewas > 0) {
        $words .= ' and ' . _claim_int_to_words($pesewas) . ' pesewa' . ($pesewas === 1 ? '' : 's');
    }
    return $words;
}

/*
 * Render the claim form as a faithful copy of the printed ACA/F/10 sheet and
 * auto-open the print dialog.
 */
/*
 * Per-stage approvals for a claim (#5). Returns a map:
 *   stage(int) => array('date' => 'Y-m-d H:i:s', 'approver' => name|null)
 * `approver` is populated only for stages approved after approver_id capture was
 * added (Option B); older approvals show the role + date only (Option A).
 */
function claim_stage_approvals($conn, $claimId) {
    $out = array();
    if (!$conn || !$claimId) return $out;
    try {
        $stmt = mysqli_prepare($conn,
            "SELECT s.stage, s.time_approved,
                    CASE WHEN ud.userId IS NULL THEN NULL
                         ELSE TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) END AS approver
             FROM claim_approval_stages s
             LEFT JOIN user_details ud ON ud.userId = s.approver_id
             WHERE s.claimId = ? AND s.status = 'Approved'
             ORDER BY s.stage, s.stageId");
        if (!$stmt) return $out;
        $claimId = (int) $claimId;
        mysqli_stmt_bind_param($stmt, 'i', $claimId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $out[(int) $row['stage']] = array('date' => $row['time_approved'], 'approver' => $row['approver']);
        }
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
        error_log('[claim_form_pdf] approvals lookup failed: ' . $e->getMessage());
    }
    return $out;
}

function render_rmu_claim_form(array $rows, $conn = null) {
    $first = $rows[0];
    $rate  = (float) $first['rate'];

    $grand_total = 0.0;
    foreach ($rows as $r) $grand_total += (float) $r['periods'] * $rate;

    $full_name = trim($first['last_name'] . ', ' . $first['first_name']
                 . (!empty($first['other_names']) ? ' ' . $first['other_names'] : ''));
    $department = isset($first['user_department']) ? (string) $first['user_department'] : '';
    $programme  = isset($first['programme']) ? (string) $first['programme'] : '';
    $course     = isset($first['course']) ? (string) $first['course'] : '';
    $classes    = isset($first['class']) ? (string) $first['class'] : '';
    $claim_id   = isset($first['claimId']) ? (int) $first['claimId'] : 0;
    $approvals  = claim_stage_approvals($conn, $claim_id);   // #5: per-stage approval stamps

    // Web-root-relative base so the crest resolves regardless of the caller's depth.
    $base = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
        ? '/claims-approval-system/' : '/';
    $logo = $base . 'login/images/rmu.jpg';

    // The paper form has ~13 body rows; pad short claims so it looks identical.
    $min_rows   = 13;
    $blank_rows = max(0, $min_rows - count($rows));

    $fmt_time = function ($t) {
        $ts = strtotime($t);
        return $ts ? date('g:i A', $ts) : (string) $t;
    };
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RMU Claim Form &mdash; <?php echo h($full_name); ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  body { font-family: "Times New Roman", Times, serif; color: #000; background: #d9dde3; font-size: 12pt; }
  .toolbar { max-width: 900px; margin: 14px auto 0; text-align: right; }
  .btn { padding: 8px 16px; border: none; border-radius: 4px; font-size: 11pt; cursor: pointer; color: #fff; margin-left: 6px; }
  .btn-print { background: #1d4ed8; }
  .btn-close { background: #6b7280; }

  .sheet {
    max-width: 900px; margin: 14px auto; background: #fff; padding: 34px 40px 26px;
    box-shadow: 0 6px 24px rgba(0,0,0,.18);
  }

  .head { position: relative; text-align: center; min-height: 66px; margin-bottom: 6px; }
  .head .crest { position: absolute; right: 0; top: 0; height: 66px; width: auto; }
  .head .uni { font-size: 15pt; font-weight: bold; text-decoration: underline; }
  .head .doc { font-size: 13pt; font-weight: bold; text-decoration: underline; margin-top: 2px; }

  .to  { margin: 14px 0 4px; }
  .dear { margin-bottom: 8px; }
  .apptitle { text-align: center; font-weight: bold; font-size: 12.5pt; margin: 6px 0 2px; }
  .intro { text-align: center; margin-bottom: 10px; }

  .fill { display: inline-block; border-bottom: 1px dotted #000; min-width: 120px; padding: 0 4px;
          line-height: 1.25; vertical-align: baseline; }

  table.claim { width: 100%; border-collapse: collapse; }
  table.claim th, table.claim td { border: 1px solid #000; padding: 5px 6px; font-size: 10.5pt; }
  table.claim th { text-align: center; font-weight: bold; line-height: 1.15; }
  table.claim td { height: 26px; }
  td.c { text-align: center; }
  td.r { text-align: right; }
  .clsline { font-size: 8.5pt; }
  .grand td { font-weight: bold; }

  .words { margin: 12px 0 4px; }
  .sig   { margin-top: 14px; }
  .approvals { margin-top: 16px; }
  .appr-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px 22px; }
  .appr-num { font-size: 10.5pt; font-weight: bold; }
  .appr-line { border-bottom: 1px dotted #000; height: 28px; margin-top: 4px; }
  .appr-cap { font-size: 8pt; color: #333; }
  .appr-stamp { border: 1px solid #000; border-radius: 4px; padding: 5px 8px; margin-top: 4px;
                font-size: 8.5pt; line-height: 1.35; }
  .appr-stamp .lead { font-weight: bold; letter-spacing: .03em; }
  .appr-stamp .who  { font-weight: bold; }
  .appr-stamp .meta { color: #333; font-size: 8pt; }

  table.docctrl { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 9.5pt; }
  table.docctrl td { border: 1px solid #000; padding: 3px 7px; }

  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .sheet { max-width: none; margin: 0; padding: 0; box-shadow: none; }
    @page { size: A4; margin: 12mm; }
    table.claim, table.docctrl, tr, td, th { break-inside: avoid; }
  }
</style>
</head>
<body>

  <div class="toolbar">
    <button class="btn btn-print" onclick="window.print()">&#128438; Print / Save as PDF</button>
    <button class="btn btn-close" onclick="window.close()">Close</button>
  </div>

  <div class="sheet">

    <div class="head">
      <img class="crest" src="<?php echo h($logo); ?>" alt="" onerror="this.style.display='none'">
      <div class="uni">REGIONAL MARITIME UNIVERSITY</div>
      <div class="doc">CLAIM FORM</div>
    </div>

    <div class="to">To the Head of <span class="fill" style="min-width:360px;"><?php echo h($department); ?></span></div>
    <div class="dear">Dear Sir,</div>

    <div class="apptitle">APPLICATION FOR PAYMENT OF LECTURING FEES TO RESOURCE PERSON</div>
    <div class="intro">I hereby submit a list of payments due me as follows:</div>

    <table class="claim">
      <thead>
        <tr>
          <th style="width:11%;">DATE</th>
          <th style="width:17%;">PROGRAMME</th>
          <th style="width:21%;">COURSE</th>
          <th style="width:9%;">FROM</th>
          <th style="width:9%;">TO</th>
          <th style="width:7%;">HRS</th>
          <th style="width:12%;">RATE<br>[GH&cent;]</th>
          <th style="width:14%;">SUB TOTAL<br>[GH&cent;]</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $periods = (int) $r['periods'];
          $sub     = $periods * $rate; ?>
        <tr>
          <td class="c"><?php echo h(date('d/m/Y', strtotime($r['claim_date']))); ?></td>
          <td><?php echo h($programme); ?></td>
          <td><?php echo h($course); ?><?php echo $classes !== '' ? '<div class="clsline">(' . h($classes) . ')</div>' : ''; ?></td>
          <td class="c"><?php echo h($fmt_time($r['start_time'])); ?></td>
          <td class="c"><?php echo h($fmt_time($r['end_time'])); ?></td>
          <td class="c"><?php echo $periods; ?></td>
          <td class="r"><?php echo number_format($rate, 2); ?></td>
          <td class="r"><?php echo number_format($sub, 2); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < $blank_rows; $i++): ?>
        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
        <tr class="grand">
          <td colspan="7" class="r">GRAND TOTAL</td>
          <td class="r"><?php echo number_format($grand_total, 2); ?></td>
        </tr>
      </tbody>
    </table>

    <div class="words">Amount in words:
      <span class="fill" style="min-width:560px;"><?php echo h(claim_amount_in_words($grand_total)); ?> only</span>
    </div>

    <div class="sig">
      Name: <span class="fill" style="min-width:280px;"><?php echo h($full_name); ?></span>
      &nbsp; Signature: <span class="fill" style="min-width:150px;"></span>
      &nbsp; Date: <span class="fill" style="min-width:120px;"></span>
    </div>

    <div class="approvals">
      <?php
        // Each printed signatory maps to its workflow stage (roles table):
        // HOD=1, Dean=2, Internal Auditor=3, VC=4, Provost=5.
        $apprSlot = function ($num, $label, $stage) use ($approvals, $claim_id) {
            $out = '<div><div class="appr-num">' . $num . '. ' . h($label) . '</div>';
            if (isset($approvals[$stage])) {
                $a    = $approvals[$stage];
                $who  = !empty($a['approver'])
                      ? h($a['approver']) . ' &mdash; ' . h($label)
                      : h($label);
                $date = !empty($a['date']) ? date('d/m/Y', strtotime($a['date'])) : '';
                $out .= '<div class="appr-stamp">'
                      . '<span class="lead">&#10003; DIGITALLY APPROVED</span><br>'
                      . '<span class="who">' . $who . '</span><br>'
                      . '<span class="meta">' . h($date)
                      . ($claim_id ? ' &middot; Claim #' . $claim_id : '') . '</span></div>';
            } else {
                $out .= '<div class="appr-line"></div><div class="appr-cap">Signature &amp; date</div>';
            }
            return $out . '</div>';
        };
      ?>
      <div class="appr-grid">
        <?php
          echo $apprSlot(1, 'Certified correct by HOD', 1);
          echo $apprSlot(2, 'Dean of Faculty', 2);
          echo $apprSlot(3, 'Provost', 5);
          echo $apprSlot(4, 'Internal Auditor', 3);
          echo $apprSlot(5, 'Approval by VC', 4);
        ?>
      </div>
    </div>

    <table class="docctrl">
      <tr>
        <td>Doc. No.: ACA/F/10</td><td>Page No.:1/1</td><td>Issue No.: 1</td><td>Last Update:15/05/15</td>
      </tr>
      <tr>
        <td>Revision No.: 3</td><td>Date:20/03/07</td><td>Author: HOD</td><td>Approved: PROVOST</td>
      </tr>
    </table>

  </div>

<script>window.onload = function () { window.print(); };</script>
</body>
</html><?php
}
