const fs = require('fs');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, LevelFormat, HeadingLevel, BorderStyle,
  WidthType, ShadingType, VerticalAlign, PageNumber, PageBreak, TableOfContents,
} = require('docx');

const FONT = 'Arial';
const NAVY = '15396B';     // headings
const BLUE = '1D4ED8';     // accent
const MUTED = '5B6B80';

// ── numbering config: bullets + many restartable ordered lists ───────────────
let _ol = 0;
const olRefs = [];
for (let i = 1; i <= 80; i++) olRefs.push('ol' + i);
const numbering = {
  config: [
    { reference: 'bul', levels: [
      { level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 540, hanging: 260 } } } },
      { level: 1, format: LevelFormat.BULLET, text: '◦', alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 1080, hanging: 260 } } } },
    ]},
    ...olRefs.map(ref => ({ reference: ref, levels: [
      { level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 540, hanging: 280 } } } },
    ]})),
  ],
};

// ── helpers ──────────────────────────────────────────────────────────────────
const T = (text, opts = {}) => new TextRun({ text, ...opts });
const toRuns = (x) => Array.isArray(x) ? x : [T(x)];

const h1 = (t) => new Paragraph({ heading: HeadingLevel.HEADING_1, children: [T(t)] });
const h2 = (t) => new Paragraph({ heading: HeadingLevel.HEADING_2, children: [T(t)] });
const h3 = (t) => new Paragraph({ heading: HeadingLevel.HEADING_3, children: [T(t)] });

const p  = (x) => new Paragraph({ spacing: { after: 130 }, children: toRuns(x) });
const lead = (label, rest) => new Paragraph({ spacing: { after: 130 },
  children: [T(label + ' ', { bold: true }), ...toRuns(rest)] });

const bul = (x) => new Paragraph({ numbering: { reference: 'bul', level: 0 }, spacing: { after: 60 }, children: toRuns(x) });
const bul2 = (x) => new Paragraph({ numbering: { reference: 'bul', level: 1 }, spacing: { after: 60 }, children: toRuns(x) });
const bulList = (arr) => arr.map(bul);

function steps(arr) {
  _ol++; const ref = olRefs[(_ol - 1) % olRefs.length];
  return arr.map(x => new Paragraph({ numbering: { reference: ref, level: 0 }, spacing: { after: 60 }, children: toRuns(x) }));
}

const note = (x) => new Paragraph({
  spacing: { before: 60, after: 140 }, indent: { left: 200 },
  border: { left: { style: BorderStyle.SINGLE, size: 18, color: BLUE, space: 12 } },
  children: [T('Note  ', { bold: true, color: BLUE }), ...toRuns(x)],
});

const border = { style: BorderStyle.SINGLE, size: 1, color: 'C9D4E5' };
const borders = { top: border, bottom: border, left: border, right: border,
  insideHorizontal: border, insideVertical: border };

function table(headers, rows, widths) {
  const total = widths.reduce((a, b) => a + b, 0);
  const mk = (text, opts) => new Paragraph({ children: toRuns(text), spacing: { after: 0 }, ...opts });
  const headRow = new TableRow({ tableHeader: true, children: headers.map((hd, i) =>
    new TableCell({ width: { size: widths[i], type: WidthType.DXA }, borders,
      shading: { fill: BLUE, type: ShadingType.CLEAR }, verticalAlign: VerticalAlign.CENTER,
      margins: { top: 70, bottom: 70, left: 110, right: 110 },
      children: [mk([T(hd, { bold: true, color: 'FFFFFF', size: 19 })])] })) });
  const bodyRows = rows.map((r, ri) => new TableRow({ children: r.map((c, i) =>
    new TableCell({ width: { size: widths[i], type: WidthType.DXA }, borders,
      shading: { fill: ri % 2 ? 'F3F6FC' : 'FFFFFF', type: ShadingType.CLEAR },
      margins: { top: 60, bottom: 60, left: 110, right: 110 },
      children: [mk(toRuns(c), {})] })) }));
  return new Table({ width: { size: total, type: WidthType.DXA }, columnWidths: widths,
    rows: [headRow, ...bodyRows] });
}

const spacer = () => new Paragraph({ spacing: { after: 60 }, children: [T('')] });

// ════════════════════════════════════════════════════════════════════════════
// CONTENT
// ════════════════════════════════════════════════════════════════════════════
const body = [];
const A = (...x) => { for (const item of x) { if (Array.isArray(item)) body.push(...item); else body.push(item); } };

// ── Title page ───────────────────────────────────────────────────────────────
A(new Paragraph({ spacing: { before: 2600, after: 0 }, alignment: AlignmentType.CENTER,
  children: [T('Regional Maritime University', { bold: true, size: 40, color: NAVY })] }));
A(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 40 },
  children: [T('Claims Management & Approval System', { size: 30, color: BLUE })] }));
A(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 240, after: 0 },
  children: [T('User Documentation', { bold: true, size: 26 })] }));
A(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 0 },
  children: [T('Complete guide to features and functionality, by user role', { italics: true, size: 22, color: MUTED })] }));
A(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 1600 },
  children: [T('Version 1.0', { size: 22 })] }));
A(new Paragraph({ alignment: AlignmentType.CENTER, children: [T('Part-time teaching payment claims', { size: 20, color: MUTED })] }));
A(new Paragraph({ children: [new PageBreak()] }));

// ── TOC ──────────────────────────────────────────────────────────────────────
A(new Paragraph({ heading: HeadingLevel.HEADING_1, children: [T('Table of Contents')] }));
A(new TableOfContents('Table of Contents', { hyperlink: true, headingStyleRange: '1-2' }));
A(new Paragraph({ children: [new PageBreak()] }));

// ── 1. Introduction ───────────────────────────────────────────────────────────
A(h1('1. Introduction'));
A(p('The RMU Claims Management & Approval System is a web application that lets part-time and supplementary teaching staff at Regional Maritime University file payment claims for the teaching sessions they have delivered, route those claims through a configurable multi-stage approval workflow, and hand approved claims to the Finance office for payment — all online, with a full audit trail.'));
A(p('This document is the complete reference for everyday users. It is organised by user role so each reader can go straight to the parts relevant to them, and it describes every screen, button and behaviour the system offers.'));

A(h2('1.1 User roles at a glance'));
A(p('Every account has exactly one role, which determines what the user can see and do.'));
A(table(
  ['Role', 'Who it is for', 'Primary responsibilities'],
  [
    ['Claimant\n(Lecturer)', 'Part-time / supplementary teaching staff', 'File, save, submit, track, clone and download claims; maintain bank details'],
    ['Approver', 'Heads, coordinators and officers who authorise claims', 'Review claims at their assigned approval stage; approve or flag (return) them'],
    ['Finance', 'Finance office / payments team', 'Process fully-approved claims for payment, export payment files, mark claims paid'],
    ['Administrator', 'System administrators', 'Manage users, courses, rank rates, settings; view reports and the audit log'],
  ],
  [1700, 3360, 4300],
));

A(h2('1.2 The claim lifecycle'));
A(p('A claim moves through a predictable set of states. Understanding these states makes the rest of this guide easier to follow.'));
A(...steps([
  [T('Draft (Saved). ', { bold: true }), T('The claimant starts a claim and saves it to finish later. Drafts are private to the claimant and can be edited or deleted at any time.')],
  [T('Pending. ', { bold: true }), T('The claimant submits the claim. It enters the approval workflow at Stage 1 and waits for the Stage 1 approver.')],
  [T('In approval. ', { bold: true }), T('Each approver in turn approves the claim, advancing it one stage at a time until the final stage configured by the administrator.')],
  [T('Flagged. ', { bold: true }), T('Any approver can flag (return) a claim with a reason. The claimant fixes the issue and resubmits.')],
  [T('Completed (Forwarded to Finance). ', { bold: true }), T('When the final stage is approved, the claim is complete and appears in the Finance queue.')],
  [T('Paid. ', { bold: true }), T('Finance records payment; the claim leaves the payment queue and appears in the paid-claims report.')],
]));
A(table(
  ['Status', 'Meaning', 'Who acts next'],
  [
    ['Saved', 'Draft, not yet submitted', 'Claimant'],
    ['Pending', 'Submitted, awaiting approval at the current stage', 'Approver (current stage)'],
    ['Flagged', 'Returned to the claimant with a reason', 'Claimant'],
    ['Forwarded to Finance', 'Fully approved / completed', 'Finance'],
    ['Paid', 'Payment recorded by Finance', '— (closed)'],
  ],
  [2600, 4360, 2400],
));

// ── 2. Getting Started ─────────────────────────────────────────────────────────
A(h1('2. Getting Started (all users)'));

A(h2('2.1 Accessing the system'));
A(p('Open the system URL provided by your administrator in a modern web browser (Google Chrome, Microsoft Edge, Firefox or Safari, kept up to date). The system is fully responsive and works on desktop, tablet and mobile screens.'));

A(h2('2.2 Creating an account (Registration)'));
A(p('There are two self-registration forms, reachable from the login page via the “Register here” link and the toggle between claimant and approver registration.'));
A(h3('Claimant registration'));
A(...steps([
  'On the login page choose “Register here”.',
  'Complete Personal Information: first name, last name, other names (optional), phone number (10 digits, starting with 0), gender and email.',
  'Choose a strong password.',
  'Complete Academic Details: faculty, department and academic rank. Your hourly rate is shown automatically based on the selected rank.',
  'Complete Banking Details: bank, branch, account name and account number.',
  'Submit. Your account is created in a disabled state — an administrator must activate it before you can sign in.',
]));
A(h3('Approver registration'));
A(p('Approvers register from the “Register as Approver” form. In addition to personal and academic details, an approver selects an Approver Rank, which automatically sets the approval stage they are responsible for. The account is created disabled and must be activated by an administrator.'));
A(note('Your pay rate is never self-set. At registration the rate is taken from your rank (claimants) and an administrator confirms it when activating your account.'));

A(h2('2.3 Logging in'));
A(...steps([
  'Enter your registered email and password on the login page.',
  'Select Sign In. You are taken to the dashboard for your role.',
]));
A(p([T('If sign-in fails: ', { bold: true }), T('check the email/password; if you see an “account disabled” message your account has not yet been activated by an administrator. After five failed attempts from the same location, sign-in is temporarily blocked for 15 minutes to protect the account.')]));

A(h2('2.4 Finding your way around'));
A(bulList([
  [T('Sidebar. ', { bold: true }), T('On the left; lists the pages available to your role. On smaller screens it collapses behind the menu (☰) button in the header.')],
  [T('Header. ', { bold: true }), T('Shows the current page title and your profile menu (with Logout).')],
  [T('Cards & tables. ', { bold: true }), T('Information is grouped into frosted “glass” cards in the university’s white-and-blue colours. Tables can be filtered and searched where relevant.')],
  [T('Status colours. ', { bold: true }), T('Green = success/active/completed, blue = in progress/primary, amber = needs attention/archived, red = flagged/destructive. Colour is always paired with a text label.')],
]));

A(h2('2.5 Changing your password'));
A(p('Open Settings from the sidebar, enter your current password and your new password twice, and save. (There is no self-service password reset; if you are locked out, ask an administrator to reset your account.)'));

A(h2('2.6 Logging out & sessions'));
A(bulList([
  'Use Logout in the sidebar or the profile menu to end your session immediately.',
  'For security, sessions automatically time out after 30 minutes of inactivity and you will be returned to the login page.',
]));

// ── 3. Claimant ───────────────────────────────────────────────────────────────
A(h1('3. Claimant (Lecturer) Guide'));
A(p('Claimants file claims for the teaching they have delivered and track them to payment. The sidebar provides Dashboard, File New Claim, My Claims, Bank Details and Settings.'));

A(h2('3.1 Maintaining your bank details'));
A(p('Finance uses your bank details to pay approved claims, so keep them accurate.'));
A(...steps([
  'Open Bank Details from the sidebar.',
  'Choose your Bank from the dropdown (populated from the university’s bank list).',
  'Choose your Branch — the branch list updates automatically to show only branches of the selected bank.',
  'Enter the Account Name and Account Number.',
  'Select Save Bank Details. A confirmation appears and your details are stored.',
]));
A(note('If no bank details are on file, a reminder banner is shown. Your saved bank and branch are pre-selected whenever you return to the page.'));

A(h2('3.2 Filing a new claim'));
A(p('“File New Claim” is the core screen. A claim has claim details (the class and course), one or more teaching sessions (time slots and the dates they were taught), and is reviewed before submission.'));

A(h3('Step 1 — Claim details'));
A(...steps([
  'Select the Department. The Programme and Course dropdowns then load the options that belong to that department.',
  'Select the Programme and the Course.',
  [T('Enter the Class ', { bold: true }), T('(for example BIT27, BEE24). Letters are automatically capitalised, and a dropdown suggests classes that already exist so you can reuse them.')],
  'Your rate per period is shown for reference (set from your rank; it cannot be edited here).',
]));

A(h3('Step 2 — Teaching sessions'));
A(p('A session is one unique time slot (start and end time). Add one session per distinct time slot, then list every date that slot was taught.'));
A(...steps([
  'Select Add Session.',
  'Enter the Start Time and End Time. The number of periods is calculated automatically (one period = 50 minutes) and the per-session amount is computed from your rate.',
  'If fuel allowance is enabled by the administrator, tick “Include Fuel Component” where applicable.',
  'Add the teaching dates for this session — either individually with Add Date, or in bulk with the recurring-date generator (below).',
  'Add more sessions as needed. The live summary shows periods, per-session amount and the grand total.',
]));

A(h3('The recurring-date generator'));
A(p('To avoid typing many dates by hand, each session has a “Generate recurring dates” tool:'));
A(...steps([
  'Choose a From date and a To date.',
  'Tick the weekday(s) the class runs (e.g. Mon, Wed).',
  'Select Generate. The system fills in every matching date in the range.',
]));
A(bulList([
  [T('Public holidays are skipped ', { bold: true }), T('automatically, and the tool tells you how many were skipped.')],
  'Dates already added are not duplicated.',
  'Generation stops at the 365-date limit per claim.',
]));

A(h3('Auto-save'));
A(p('Your work is saved automatically as a draft a few seconds after you make changes, and again periodically, so you will not lose it. A status indicator shows “Unsaved changes”, “Saving…” or “Draft saved”. You can also press Save Draft at any time.'));

A(h3('Step 3 — Review and submit'));
A(p('Selecting Submit Claim (or Save Draft) opens an editable review window so you can confirm everything before committing:'));
A(bulList([
  'The claim’s class, course, programme, department and rate are shown at the top.',
  'Every session date is listed in a table with the day and date (e.g. Mon 13/07/2026), time, periods and amount, plus a running grand total.',
  [T('Remove a date ', { bold: true }), T('by selecting the × next to it — the form updates instantly and the total recalculates.')],
  'Select Confirm & Submit (or Confirm & Save Draft) to finish, or Cancel to keep editing.',
]));
A(note('Overlap protection: if a session’s date and time overlap a claim you have already submitted (or another session in the same claim), the system rejects it and tells you which date conflicts — preventing accidental double-claiming.'));

A(h2('3.3 Managing your claims (My Claims)'));
A(p('“My Claims” is your control centre. At the top, four cards summarise how many claims you have that are Flagged, Pending, Saved (drafts) and Completed. Selecting a card — or a filter tab — narrows the view to that group, and a search box filters by course, class or department.'));
A(p('Claims are grouped into four sections, each with the relevant actions:'));
A(table(
  ['Section', 'What it contains', 'Available actions'],
  [
    ['Flagged', 'Claims returned to you with a reason', 'View details; Resubmit (opens an editable copy)'],
    ['Pending', 'Submitted claims awaiting approval (with a stage progress bar)', 'View details'],
    ['Saved Drafts', 'Unsubmitted claims, with a session count', 'Edit; Submit (if it has sessions); Delete'],
    ['Completed', 'Fully approved claims forwarded to Finance', 'View details; Download form; Clone as a new draft'],
  ],
  [1700, 4060, 3600],
));
A(h3('Claim actions explained'));
A(bulList([
  [T('View details ', { bold: true }), T('— opens a read-only window with the claimant/course information and every session (date, time, periods, amount). The fuel column appears only if the claim uses a fuel component.')],
  [T('Edit ', { bold: true }), T('(drafts) — reopens the draft in File New Claim to continue working.')],
  [T('Submit ', { bold: true }), T('(drafts) — sends the draft for approval after the review window. Disabled until the draft has at least one session.')],
  [T('Delete ', { bold: true }), T('(drafts) — permanently removes a draft after confirmation.')],
  [T('Resubmit ', { bold: true }), T('(flagged) — creates an editable copy so you can fix the flagged issue and submit again.')],
  [T('Download form ', { bold: true }), T('(completed) — opens the official claim form for printing or saving as PDF.')],
  [T('Clone ', { bold: true }), T('(completed) — creates a new draft pre-filled with the same course and sessions, so a recurring claim can be filed in seconds.')],
]));

// ── 4. Approver ────────────────────────────────────────────────────────────────
A(h1('4. Approver Guide'));
A(p('Approvers authorise claims at a specific stage of the workflow. Your assigned stage is set from your approver rank; you only act on claims that have reached your stage, and (from Stage 2 onward) you can filter by department.'));

A(h2('4.1 The approval queue'));
A(p('Your dashboard lists the claims currently waiting at your stage. Each row shows the claimant, department, course and submission date. A department filter is available where relevant.'));

A(h2('4.2 Reviewing a claim'));
A(p('Select the View (eye) action to open the claim. The window shows the claim’s class, course, programme, department and rate, followed by a table of every teaching session — date (with day, e.g. Mon 13/07/2026), start/end time, periods, rate, amount and (if used) fuel — and the grand total.'));

A(h2('4.3 Approving and flagging'));
A(bulList([
  [T('Approve ', { bold: true }), T('— confirms the claim at your stage. It then advances to the next stage, or, if yours is the final stage, the claim is completed and forwarded to Finance.')],
  [T('Flag ', { bold: true }), T('— returns the claim to the claimant. You must enter a reason; the claimant sees this reason and can fix and resubmit.')],
]));
A(note('Stage protection: the system verifies a claim is genuinely pending at your stage before it acts, so claims cannot be approved out of order or by the wrong stage.'));

A(h2('4.4 Bulk approve / flag'));
A(p('To process many claims at once:'));
A(...steps([
  'Tick the checkbox on each claim you want to act on, or use the header checkbox to select all visible claims.',
  'A bulk action bar appears showing how many are selected.',
  'Choose Approve Selected, or Flag Selected (which asks once for a shared reason).',
  'The system processes each one, applying the same stage checks as a single action, and reports how many were approved/flagged, skipped or failed.',
]));

// ── 5. Finance ─────────────────────────────────────────────────────────────────
A(h1('5. Finance Guide'));
A(p('Finance processes fully-approved (completed) claims for payment. The sidebar provides the Finance Dashboard and the Paid Claims report.'));

A(h2('5.1 Finance Dashboard — the payment queue'));
A(p('The dashboard lists completed claims that have not yet been paid. For each claim you can:'));
A(bulList([
  [T('PDF ', { bold: true }), T('— open the printable claim form for that claimant.')],
  [T('Mark Paid ', { bold: true }), T('— record payment. A window lets you enter an optional payment reference; on confirmation the claim is stamped with who paid it and when, and it leaves the queue.')],
]));
A(h3('Bulk export options'));
A(p('Above the queue, three buttons act on all completed, unpaid claims at once:'));
A(table(
  ['Button', 'Output', 'Use it for'],
  [
    ['Export CSV', 'Detailed spreadsheet: claimant, department, rank, rate, totals and bank details', 'Reconciliation and record-keeping'],
    ['Payment Batch', 'Lean CSV: account name, number, bank, branch, amount, reference', 'Uploading to the bank’s bulk-payment system'],
    ['Download All Forms', 'A ZIP of every claim form as a Word document', 'Printing or filing the official forms in one step'],
  ],
  [2200, 4760, 2400],
));

A(h2('5.2 Paid Claims (reporting & audit trail)'));
A(p('The Paid Claims page is a searchable record of every processed payment. It offers:'));
A(bulList([
  'Filters: paid-date range, department, and a search box that matches claimant name or payment reference.',
  'Summary cards showing the number of paid claims and the total amount for the current filter.',
  'A table of each payment: claim, claimant, department, course, amount, payment reference, who processed it and when.',
  [T('Export CSV ', { bold: true }), T('— downloads the processed-payments list (including references) for the active filters.')],
]));

// ── 6. Administrator ────────────────────────────────────────────────────────────
A(h1('6. Administrator Guide'));
A(p('Administrators manage the people, reference data and settings that the rest of the system depends on. The sidebar provides Dashboard, Users, Bulk Import, Claims, Courses, Logs, Reports, Rank Rates and Settings.'));

A(h2('6.1 Dashboard'));
A(p('The admin dashboard shows headline counts (total users, active users, disabled users, total claims, flagged claims) and a list of accounts pending activation, each with quick Activate and View actions.'));

A(h2('6.2 Users'));
A(p('The Users page lists every account with filters for department, role and status, plus search.'));
A(bulList([
  [T('View ', { bold: true }), T('— see a user’s full profile.')],
  [T('Edit ', { bold: true }), T('— change a user’s details (including rank, rate and department).')],
  [T('Activate / Disable ', { bold: true }), T('— control whether a user can sign in. New self-registered accounts arrive disabled and must be activated here.')],
]));
A(note('When you activate a user who has no rate yet, the system auto-fills their rate from their rank, so claimants are ready to file immediately.'));

A(h2('6.3 Bulk Import'));
A(p('The Bulk Import page loads many records at once from CSV files — Users, Bank Branches and Courses. Each importer has:'));
A(bulList([
  [T('A Download template button ', { bold: true }), T('that gives you a CSV with the exact column headers (and a sample row) to fill in.')],
  'A file picker and Upload & Import button.',
  'A results summary showing how many rows were added/updated and how many were skipped, with the reason for each skip.',
]));
A(table(
  ['Import', 'Required columns', 'Optional columns'],
  [
    ['Users', 'first_name, last_name, phone_number, gender, email, faculty, department, rank', 'other_names'],
    ['Bank Branches', 'bank_name, bank_branch, branch_code', '—'],
    ['Courses', 'code, name, department', 'credit_hours, contact_hours'],
  ],
  [1700, 5160, 2500],
));
A(bulList([
  'Imported users are created disabled as claimants, with the rate set from their rank and a temporary password generated for each (shown in the results so you can distribute them securely).',
  'Bank branches use branch_code as the unique key, so re-importing is safe.',
  'Courses use code as the unique key (re-importing updates the course); rows whose department does not exist are skipped and listed.',
]));

A(h2('6.4 Courses'));
A(p('Courses must exist for a department before claimants can pick them when filing. The Courses page lets you manage them without touching the database:'));
A(bulList([
  'Filter by department or status (active/archived) and search by code or name.',
  [T('Add Course ', { bold: true }), T('— enter a code, name, department (chosen from a dropdown) and optional credit/contact hours.')],
  [T('Edit ', { bold: true }), T('— update a course (the code is the key and is fixed).')],
  [T('Archive / Restore ', { bold: true }), T('— archived courses stop appearing in the claim form but are not deleted; restore brings them back.')],
]));
A(note('If claimants report an empty course list for a department, it means that department has no active courses yet — add or import them here.'));

A(h2('6.5 Claims overview'));
A(p('The Claims page lists every submitted claim across all departments, with filters for department, programme, course and status, and a derived status (Pending / Flagged / Completed) for each.'));

A(h2('6.6 Rank Rates'));
A(p('Rank Rates is where pay rates per academic rank are maintained. Editing a rank’s rate and saving propagates the new rate to all users on that rank, so a single change updates everyone consistently.'));

A(h2('6.7 Logs (audit trail)'));
A(p('The Logs page is an immutable record of significant actions — sign-ins, registrations, claim submissions, approvals, flags, payments, imports, course and rate changes, and exports. Each entry shows the timestamp, the person, their role, a plain-English action, the affected item and the originating IP address.'));
A(bulList([
  'Filter by action, actor role, entity type and date range.',
  [T('Export ', { bold: true }), T('the filtered log as CSV or as a print-ready PDF.')],
]));

A(h2('6.8 Reports'));
A(p('The Reports page produces filtered, sortable claim reports. Filter by department, programme, course, status and submission-date range; sort by claimant, department or date; then export the result as CSV or PDF.'));

A(h2('6.9 Settings'));
A(p('Settings holds system-wide configuration, including the maximum number of approval stages (how many approvers a claim must pass) and the fuel component (whether the fuel allowance is enabled and its amount per session).'));

// ── 7. Common features ──────────────────────────────────────────────────────────
A(h1('7. Common Features & Conventions'));
A(bulList([
  [T('Confirmations & messages. ', { bold: true }), T('Actions that change data ask for confirmation and report success or failure with a clear on-screen message.')],
  [T('Dates. ', { bold: true }), T('Teaching dates are shown with the weekday for clarity (e.g. Mon 13/07/2026).')],
  [T('Search & filter. ', { bold: true }), T('Long tables provide filters and a search box; counts update as you filter.')],
  [T('Accessibility. ', { bold: true }), T('The interface is keyboard-navigable with visible focus, uses readable colour contrast, never relies on colour alone for meaning, and respects the “reduced motion” system preference.')],
  [T('Security. ', { bold: true }), T('All forms are protected against cross-site request forgery, passwords are stored hashed, sign-in attempts are rate-limited, and sessions expire after inactivity.')],
]));

// ── 8. Troubleshooting ──────────────────────────────────────────────────────────
A(h1('8. Troubleshooting & FAQ'));
A(table(
  ['Symptom', 'Cause / Resolution'],
  [
    ['I can’t sign in / “account disabled”', 'A new account must be activated by an administrator before first use. Confirm with your admin.'],
    ['Too many failed attempts', 'Sign-in is locked for 15 minutes after 5 failures from the same location. Wait and try again.'],
    ['The Course dropdown is empty', 'That department has no active courses yet. An administrator must add or import courses for it (Courses / Bulk Import).'],
    ['Programme/Course don’t change with department', 'Reselect the Department; the dependent lists reload. If still empty, the data has not been set up for that department.'],
    ['I forgot my password', 'There is no self-service reset. Ask an administrator to reset your account.'],
    ['My bank/branch isn’t listed', 'Ask an administrator to import the missing bank branch (Bulk Import → Bank Branches).'],
    ['My claim was returned (Flagged)', 'Open My Claims → Flagged, read the reason, choose Resubmit, fix the issue and submit again.'],
    ['I was logged out unexpectedly', 'Sessions expire after 30 minutes of inactivity. Sign in again.'],
  ],
  [3400, 5960],
));

// ── Appendix ────────────────────────────────────────────────────────────────────
A(h1('Appendix A. Glossary'));
A(table(
  ['Term', 'Definition'],
  [
    ['Period', 'A unit of teaching time equal to 50 minutes; periods are calculated automatically from a session’s start and end time.'],
    ['Session (time slot)', 'A unique start–end time within a claim; each session lists the dates it was taught.'],
    ['Class', 'The student group a claim is for, e.g. BIT27 or BEE24.'],
    ['Stage', 'One step of the approval workflow; the number of stages is set by the administrator.'],
    ['Fuel component', 'An optional per-session allowance, enabled and valued in Settings.'],
    ['Draft', 'A saved but unsubmitted claim, private to the claimant.'],
    ['Completed', 'A fully approved claim, forwarded to Finance for payment.'],
  ],
  [2600, 6760],
));

// ════════════════════════════════════════════════════════════════════════════
const doc = new Document({
  creator: 'RMU Claims System',
  title: 'RMU Claims System — User Documentation',
  styles: {
    default: { document: { run: { font: FONT, size: 21 } } },
    paragraphStyles: [
      { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 30, bold: true, font: FONT, color: NAVY },
        paragraph: { spacing: { before: 320, after: 160 }, outlineLevel: 0,
          border: { bottom: { style: BorderStyle.SINGLE, size: 8, color: BLUE, space: 6 } } } },
      { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 25, bold: true, font: FONT, color: NAVY },
        paragraph: { spacing: { before: 240, after: 120 }, outlineLevel: 1 } },
      { id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 22, bold: true, font: FONT, color: BLUE },
        paragraph: { spacing: { before: 180, after: 80 }, outlineLevel: 2 } },
    ],
  },
  numbering,
  sections: [{
    properties: { page: {
      size: { width: 12240, height: 15840 },
      margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
    } },
    headers: { default: new Header({ children: [ new Paragraph({
      alignment: AlignmentType.RIGHT, spacing: { after: 0 },
      border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: 'C9D4E5', space: 4 } },
      children: [T('RMU Claims System — User Documentation', { size: 16, color: MUTED })],
    }) ] }) },
    footers: { default: new Footer({ children: [ new Paragraph({
      alignment: AlignmentType.CENTER, spacing: { before: 0 },
      children: [ T('Page ', { size: 16, color: MUTED }),
        new TextRun({ children: [PageNumber.CURRENT], size: 16, color: MUTED }),
        T(' of ', { size: 16, color: MUTED }),
        new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 16, color: MUTED }) ],
    }) ] }) },
    children: body,
  }],
});

const outDir = 'C:/wamp64/www/claims-approval-system/docs';
fs.mkdirSync(outDir, { recursive: true });
const outFile = outDir + '/RMU_Claims_System_User_Guide.docx';
Packer.toBuffer(doc).then(buf => { fs.writeFileSync(outFile, buf); console.log('WROTE ' + outFile + ' (' + buf.length + ' bytes)'); });
