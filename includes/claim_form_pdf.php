<?php
/*
 * Shared renderer for the RMU claim form PDF.
 *
 * Produces the "Application for Payment of Lecturing Fees to Resource Person"
 * claim as a clean, modern, print-ready document. Used by both the claimant and
 * finance downloads (users/user/.../downloadClaimPDF.inc.php and
 * users/finance/downloadClaimPDF.inc.php) so the output is identical.
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

/* Render the full HTML document for the claim form and auto-open the print dialog. */
function render_rmu_claim_form(array $rows, array $opts = array()) {
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
    $claim_id   = isset($first['claimId']) ? (string) $first['claimId'] : '';

    // Web-root-relative base so the crest resolves regardless of the caller's depth.
    $base = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
        ? '/claims-approval-system/' : '/';
    $logo = $base . 'login/images/rmu.jpg';

    $fmt_time = function ($t) {
        $ts = strtotime($t);
        return $ts ? date('g:i A', $ts) : (string) $t;
    };
    $dash = '<span class="muted">&mdash;</span>';
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RMU Claim Form &mdash; <?php echo h($full_name); ?></title>
<style>
  :root {
    --ink:#0f2744; --brand:#1d4ed8; --brand-dark:#173a9e;
    --muted:#5b6b82; --line:#d9e0ea; --line-strong:#b9c4d4;
    --tint:#f1f5fc; --band:#0f2744; --paper:#ffffff;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  html { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  body {
    font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    color:var(--ink); background:#eef1f6; line-height:1.5;
    font-size:13px; padding:24px 16px;
  }
  .sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
  .muted { color:var(--muted); }

  /* Screen toolbar */
  .toolbar {
    max-width:900px; margin:0 auto 14px; display:flex; justify-content:flex-end; gap:10px;
  }
  .btn { padding:9px 18px; border:none; border-radius:8px; font-size:13px; font-weight:600;
         cursor:pointer; color:#fff; display:inline-flex; align-items:center; gap:7px; }
  .btn-print { background:var(--brand); }
  .btn-print:hover { background:var(--brand-dark); }
  .btn-close { background:#64748b; }
  .btn:focus-visible { outline:3px solid #93b4ff; outline-offset:2px; }

  /* The document page */
  .page {
    max-width:900px; margin:0 auto; background:var(--paper);
    border-radius:12px; box-shadow:0 8px 30px rgba(15,39,68,.12);
    overflow:hidden;
  }
  .page__inner { padding:34px 40px 30px; }

  /* Header */
  .doc-head { display:flex; align-items:center; justify-content:space-between; gap:20px; }
  .brand { display:flex; align-items:center; gap:16px; min-width:0; }
  .brand .crest { height:58px; width:auto; flex:none; }
  .uni { font-size:20px; font-weight:700; letter-spacing:-.01em; line-height:1.15; }
  .doc-sub { font-size:12px; color:var(--muted); margin-top:2px; }
  .doc-meta { text-align:right; font-size:12px; color:var(--muted); flex:none; }
  .doc-meta b { display:block; color:var(--ink); font-size:14px; font-weight:700; }
  .doc-meta div + div { margin-top:6px; }
  .accent { height:4px; background:linear-gradient(90deg,var(--brand),#4f7cf0); margin:18px 0 0; border-radius:3px; }

  /* Application title */
  .apptitle { margin-top:22px; }
  .eyebrow { font-size:10.5px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--brand); }
  .apptitle h1 { font-size:17px; font-weight:700; margin-top:4px; }
  .lede { color:var(--muted); font-size:12.5px; margin-top:3px; }

  /* Meta grid */
  .meta-grid {
    margin-top:18px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px 28px; padding:16px 18px; background:var(--tint); border-radius:10px;
  }
  .meta .k { display:block; font-size:10px; font-weight:700; letter-spacing:.08em;
             text-transform:uppercase; color:var(--muted); }
  .meta .v { display:block; font-size:13.5px; font-weight:600; margin-top:2px; }

  /* Table */
  .table-wrap { margin-top:20px; overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
  table.claim { width:100%; border-collapse:collapse; min-width:720px; font-size:12px; }
  table.claim caption { text-align:left; }
  table.claim thead th {
    background:var(--band); color:#fff; font-weight:600; font-size:10.5px;
    letter-spacing:.05em; text-transform:uppercase; text-align:left;
    padding:11px 12px; white-space:nowrap;
  }
  table.claim thead th.num { text-align:right; }
  table.claim thead th.ctr { text-align:center; }
  table.claim tbody td { padding:10px 12px; border-top:1px solid var(--line); vertical-align:top; }
  table.claim tbody tr:nth-child(even) td { background:#f7f9fd; }
  td.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
  td.ctr { text-align:center; white-space:nowrap; }
  .course-name { font-weight:600; }
  .prog { color:var(--muted); }
  tfoot .grand td { border-top:2px solid var(--line-strong); padding:12px; font-weight:700; font-size:13px; background:var(--tint); }
  tfoot .grand .label { text-align:right; letter-spacing:.04em; text-transform:uppercase; font-size:11px; color:var(--muted); }
  tfoot .grand .amt { text-align:right; font-size:15px; }

  /* Amount in words */
  .words { margin-top:16px; padding:13px 16px; border:1px dashed var(--line-strong);
           border-radius:10px; background:#fff; }
  .words .k { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
  .words .v { font-size:14px; font-weight:700; margin-top:3px; }

  /* Sign-offs */
  .signoffs { margin-top:26px; }
  .sign-claimant { display:grid; grid-template-columns:2fr 1fr 1fr; gap:22px; margin-bottom:26px; }
  .field .line { border-bottom:1.5px solid var(--ink); height:26px; }
  .field .lab { font-size:10.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); margin-top:6px; }
  .field .pre { font-size:13.5px; font-weight:600; padding-bottom:2px; }

  .approvals h2 { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
                  color:var(--muted); margin-bottom:12px; }
  .approvals-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:22px 26px; }
  .appr { }
  .appr .line { border-bottom:1.5px solid var(--ink); height:30px; }
  .appr .lab { font-size:11px; margin-top:6px; }
  .appr .lab b { display:block; color:var(--ink); }
  .appr .lab span { color:var(--muted); font-size:10px; }

  /* Document-control footer */
  .doc-control {
    margin-top:26px; border-top:1px solid var(--line); padding-top:12px;
    display:flex; flex-wrap:wrap; gap:6px 22px; font-size:10px; color:var(--muted);
  }
  .doc-control b { color:var(--ink); font-weight:600; }

  /* Responsive (on-screen) */
  @media (max-width:640px) {
    body { padding:12px 8px; }
    .page__inner { padding:20px 16px; }
    .doc-head { flex-direction:column; align-items:flex-start; }
    .doc-meta { text-align:left; }
    .uni { font-size:17px; }
    .meta-grid { grid-template-columns:1fr; }
    .sign-claimant { grid-template-columns:1fr; gap:16px; }
    .approvals-grid { grid-template-columns:1fr; }
  }

  /* Print */
  @media print {
    body { background:#fff; padding:0; font-size:11px; }
    .toolbar { display:none; }
    .page { max-width:none; box-shadow:none; border-radius:0; }
    .page__inner { padding:0; }
    .table-wrap { overflow:visible; }
    table.claim { min-width:0; }
    @page { size:A4; margin:12mm; }
    .page, .table-wrap, .signoffs, tr, td, th { break-inside:avoid; }
  }
</style>
</head>
<body>

  <div class="toolbar">
    <button class="btn btn-print" onclick="window.print()">&#128438; Print / Save as PDF</button>
    <button class="btn btn-close" onclick="window.close()">Close</button>
  </div>

  <div class="page">
    <div class="page__inner">

      <header class="doc-head">
        <div class="brand">
          <img class="crest" src="<?php echo h($logo); ?>" alt="Regional Maritime University crest" onerror="this.style.display='none'">
          <div>
            <div class="uni">Regional Maritime University</div>
            <div class="doc-sub">Claim Form &middot; Lecturing Fees</div>
          </div>
        </div>
        <div class="doc-meta">
          <?php if ($claim_id !== ''): ?><div><span>Claim No.</span><b>#<?php echo h($claim_id); ?></b></div><?php endif; ?>
          <div><span>Issued</span><b><?php echo h(date('d/m/Y')); ?></b></div>
        </div>
      </header>

      <div class="accent"></div>

      <div class="apptitle">
        <div class="eyebrow">Application for Payment</div>
        <h1>Lecturing Fees to Resource Person</h1>
        <p class="lede">To the Head of <?php echo $department !== '' ? h($department) : $dash; ?> &mdash; I hereby submit a list of payments due me as follows:</p>
      </div>

      <section class="meta-grid" aria-label="Claim details">
        <div class="meta"><span class="k">Resource Person</span><span class="v"><?php echo h($full_name); ?></span></div>
        <div class="meta"><span class="k">Department</span><span class="v"><?php echo $department !== '' ? h($department) : $dash; ?></span></div>
        <div class="meta"><span class="k">Programme</span><span class="v"><?php echo $programme !== '' ? h($programme) : $dash; ?></span></div>
        <div class="meta"><span class="k">Rate</span><span class="v">GH&cent; <?php echo number_format($rate, 2); ?> <span class="muted" style="font-weight:400;">/ period</span></span></div>
      </section>

      <div class="table-wrap">
        <table class="claim">
          <caption class="sr-only">Payments due, by teaching session</caption>
          <thead>
            <tr>
              <th scope="col">Date</th>
              <th scope="col">Programme</th>
              <th scope="col">Course</th>
              <th scope="col">Class</th>
              <th scope="col" class="ctr">From</th>
              <th scope="col" class="ctr">To</th>
              <th scope="col" class="ctr">Hrs</th>
              <th scope="col" class="num">Rate (GH&cent;)</th>
              <th scope="col" class="num">Subtotal (GH&cent;)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r):
              $periods = (int) $r['periods'];
              $sub     = $periods * $rate; ?>
            <tr>
              <td class="ctr"><?php echo h(date('d/m/Y', strtotime($r['claim_date']))); ?></td>
              <td class="prog"><?php echo $programme !== '' ? h($programme) : $dash; ?></td>
              <td class="course-name"><?php echo $course !== '' ? h($course) : $dash; ?></td>
              <td><?php echo $classes !== '' ? h($classes) : $dash; ?></td>
              <td class="ctr"><?php echo h($fmt_time($r['start_time'])); ?></td>
              <td class="ctr"><?php echo h($fmt_time($r['end_time'])); ?></td>
              <td class="ctr"><?php echo $periods; ?></td>
              <td class="num"><?php echo number_format($rate, 2); ?></td>
              <td class="num"><?php echo number_format($sub, 2); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="grand">
              <td colspan="8" class="label">Grand Total (GH&cent;)</td>
              <td class="amt num"><?php echo number_format($grand_total, 2); ?></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="words">
        <span class="k">Amount in words</span>
        <div class="v"><?php echo h(claim_amount_in_words($grand_total)); ?> only</div>
      </div>

      <section class="signoffs" aria-label="Signatures and approvals">
        <div class="sign-claimant">
          <div class="field">
            <div class="pre"><?php echo h($full_name); ?></div>
            <div class="line"></div>
            <div class="lab">Name</div>
          </div>
          <div class="field">
            <div class="pre">&nbsp;</div>
            <div class="line"></div>
            <div class="lab">Signature</div>
          </div>
          <div class="field">
            <div class="pre"><?php echo h(date('d/m/Y')); ?></div>
            <div class="line"></div>
            <div class="lab">Date</div>
          </div>
        </div>

        <div class="approvals">
          <h2>Certification &amp; Approval</h2>
          <div class="approvals-grid">
            <div class="appr"><div class="line"></div><div class="lab"><b>1. Head of Department</b><span>Signature &amp; date</span></div></div>
            <div class="appr"><div class="line"></div><div class="lab"><b>2. Dean of Faculty</b><span>Signature &amp; date</span></div></div>
            <div class="appr"><div class="line"></div><div class="lab"><b>3. Provost</b><span>Signature &amp; date</span></div></div>
            <div class="appr"><div class="line"></div><div class="lab"><b>4. Internal Auditor</b><span>Signature &amp; date</span></div></div>
            <div class="appr"><div class="line"></div><div class="lab"><b>5. Approval by VC</b><span>Signature &amp; date</span></div></div>
          </div>
        </div>
      </section>

      <footer class="doc-control">
        <span><b>Doc. No.</b> ACA/F/10</span>
        <span><b>Revision</b> 3</span>
        <span><b>Dated</b> 20/03/07</span>
        <span><b>Issue</b> 1</span>
        <span><b>Last update</b> 15/05/15</span>
        <span><b>Author</b> HOD</span>
        <span><b>Approved</b> PROVOST</span>
      </footer>

    </div>
  </div>

<script>window.onload = function () { window.print(); };</script>
</body>
</html><?php
}
