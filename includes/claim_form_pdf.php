<?php
/*
 * Shared renderer for the official RMU claim form PDF.
 *
 * Reproduces the printed "APPLICATION FOR PAYMENT OF LECTURING FEES TO RESOURCE
 * PERSON" form (Doc. No. ACA/F/10) so the claimant and finance downloads look
 * identical to the paper form. Both users/user/.../downloadClaimPDF.inc.php and
 * users/finance/downloadClaimPDF.inc.php call render_rmu_claim_form($rows).
 *
 * $rows is the claim's teaching-session rows (as returned by
 * db_get_claim_download_data / the finance query) sharing one header. Expected
 * keys per row: first_name, last_name, other_names, user_department, rate,
 * programme, course, class, claim_date, start_time, end_time, periods.
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

/* Render the full HTML document for the claim form and auto-open the print dialog. */
function render_rmu_claim_form(array $rows, array $opts = array()) {
    $first = $rows[0];
    $rate  = (float) $first['rate'];

    $grand_total = 0.0;
    foreach ($rows as $r) $grand_total += (float) $r['periods'] * $rate;

    $full_name = trim($first['last_name'] . ', ' . $first['first_name']
                 . (!empty($first['other_names']) ? ' ' . $first['other_names'] : ''));
    $department = isset($first['user_department']) ? $first['user_department'] : '';
    $programme  = isset($first['programme']) ? $first['programme'] : '';
    $course     = isset($first['course']) ? $first['course'] : '';
    $classes    = isset($first['class']) ? $first['class'] : '';

    // Web-root-relative base so the crest resolves regardless of the caller's depth.
    $base = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
        ? '/claims-approval-system/' : '/';
    $logo = $base . 'login/images/rmu.jpg';

    // Pad to a minimum number of rows so short claims still look like the form.
    $min_rows = 12;
    $data_count = count($rows);
    $blank_rows = max(0, $min_rows - $data_count);

    $fmt_time = function ($t) {
        $ts = strtotime($t);
        return $ts ? date('g:i A', $ts) : h($t);
    };
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RMU Claim Form &mdash; <?php echo h($full_name); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Times New Roman", Georgia, serif; font-size: 12pt; color: #000; background: #fff; padding: 26px 30px; }
.sheet { max-width: 900px; margin: 0 auto; }
.head { position: relative; text-align: center; margin-bottom: 14px; }
.head .crest { position: absolute; right: 0; top: 0; height: 62px; }
.uni { font-size: 15pt; font-weight: bold; letter-spacing: .02em; }
.formtitle { font-size: 13pt; font-weight: bold; text-decoration: underline; margin-top: 2px; }
.addr { margin: 10px 0 2px; }
.dots { border-bottom: 1px dotted #000; display: inline-block; min-width: 260px; }
.apptitle { text-align: center; font-weight: bold; margin: 12px 0 2px; font-size: 12.5pt; }
.subtitle { text-align: center; font-style: italic; margin-bottom: 10px; }
table.claim { width: 100%; border-collapse: collapse; }
table.claim th, table.claim td { border: 1px solid #000; padding: 5px 6px; font-size: 10.5pt; }
table.claim th { text-align: center; font-weight: bold; }
table.claim td.num { text-align: right; }
table.claim td.ctr { text-align: center; }
.cls { font-size: 8.5pt; color: #333; }
.grand td { font-weight: bold; }
.rowline { height: 26px; }
.words { margin: 12px 0 2px; }
.words .val { font-weight: bold; }
.sig-line { margin-top: 14px; }
.approvals { margin-top: 18px; line-height: 2.1; }
.footer-doc { width: 100%; border-collapse: collapse; margin-top: 22px; font-size: 9pt; }
.footer-doc td { border: 1px solid #000; padding: 3px 7px; }
.no-print { text-align: right; margin-bottom: 14px; }
.btn { padding: 8px 18px; cursor: pointer; border: none; border-radius: 4px; font-size: 10.5pt; margin-left: 6px; color: #fff; }
.btn-print { background: #1d4ed8; }
.btn-close { background: #6b7280; }
@media print { .no-print { display: none; } body { padding: 0; } @page { size: A4; margin: 12mm; } }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn btn-print" onclick="window.print()">&#128438; Print / Save as PDF</button>
  <button class="btn btn-close" onclick="window.close()">Close</button>
</div>

<div class="sheet">

  <div class="head">
    <img class="crest" src="<?php echo h($logo); ?>" alt="" onerror="this.style.display='none'">
    <div class="uni">REGIONAL MARITIME UNIVERSITY</div>
    <div class="formtitle">CLAIM FORM</div>
  </div>

  <div class="addr">To the Head of <span class="dots"><?php echo h($department); ?></span></div>
  <div class="addr">Dear Sir,</div>

  <div class="apptitle">APPLICATION FOR PAYMENT OF LECTURING FEES TO RESOURCE PERSON</div>
  <div class="subtitle">I hereby submit a list of payments due me as follows:</div>

  <table class="claim">
    <thead>
      <tr>
        <th>DATE</th><th>PROGRAMME</th><th>COURSE</th><th>FROM</th><th>TO</th>
        <th>HRS</th><th>RATE<br>[GH&cent;]</th><th>SUB TOTAL<br>[GH&cent;]</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r):
        $periods = (int) $r['periods'];
        $sub     = $periods * $rate; ?>
      <tr>
        <td class="ctr"><?php echo h(date('d/m/Y', strtotime($r['claim_date']))); ?></td>
        <td><?php echo h($programme); ?></td>
        <td><?php echo h($course); ?><?php echo $classes !== '' ? '<div class="cls">' . h($classes) . '</div>' : ''; ?></td>
        <td class="ctr"><?php echo h($fmt_time($r['start_time'])); ?></td>
        <td class="ctr"><?php echo h($fmt_time($r['end_time'])); ?></td>
        <td class="ctr"><?php echo $periods; ?></td>
        <td class="num"><?php echo number_format($rate, 2); ?></td>
        <td class="num"><?php echo number_format($sub, 2); ?></td>
      </tr>
      <?php endforeach; ?>
      <?php for ($i = 0; $i < $blank_rows; $i++): ?>
      <tr class="rowline"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
      <?php endfor; ?>
      <tr class="grand">
        <td colspan="7" style="text-align:right;">GRAND TOTAL</td>
        <td class="num"><?php echo number_format($grand_total, 2); ?></td>
      </tr>
    </tbody>
  </table>

  <div class="words">Amount in words:
    <span class="val"><?php echo h(claim_amount_in_words($grand_total)); ?> only</span>
  </div>

  <div class="sig-line">
    Name: <span class="dots" style="min-width:300px;"><?php echo h($full_name); ?></span>
    &nbsp;&nbsp; Signature: <span class="dots" style="min-width:150px;"></span>
    &nbsp;&nbsp; Date: <span class="dots" style="min-width:120px;"><?php echo h(date('d/m/Y')); ?></span>
  </div>

  <div class="approvals">
    1. Certified correct by HOD: <span class="dots"></span>
    &nbsp; 2. Dean of Faculty: <span class="dots"></span>
    &nbsp; 3. PROVOST: <span class="dots"></span><br>
    4. Internal Auditor: <span class="dots"></span>
    &nbsp; 5. Approval by VC: <span class="dots"></span>
  </div>

  <table class="footer-doc">
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
