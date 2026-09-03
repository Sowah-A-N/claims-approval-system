<?php
/*
 * Shared renderer for the custom HTTP error pages.
 *
 * Deliberately self-contained — no database, no app includes — so it still
 * renders when the application or its database is unavailable (e.g. a 500).
 * Callers set $err_code, $err_title and $err_msg, then require this file.
 */
$err_code  = isset($err_code)  ? (int) $err_code : 500;
$err_title = isset($err_title) ? $err_title : 'Something went wrong';
$err_msg   = isset($err_msg)   ? $err_msg   : 'An unexpected error occurred.';

if (!headers_sent()) {
    http_response_code($err_code);
}

// Host-aware base path so the "return" link works on WAMP (/claims-approval-system/)
// and on a domain-root production deployment (/).
$base = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? '/claims-approval-system/' : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="RMU Claims Approval System error page.">
  <title><?php echo (int) $err_code; ?> — <?php echo htmlspecialchars($err_title, ENT_QUOTES, 'UTF-8'); ?> · RMU Claims</title>
  <style>
    :root{
      --bg:#f4f7f9; --card:#ffffff; --ink:#023047; --muted:#5b6b80;
      --accent:#219ebc; --accent-ink:#ffffff; --border:#dce4e9;
    }
    @media (prefers-color-scheme: dark){
      :root{ --bg:#08171f; --card:#0f2733; --ink:#e7eef2; --muted:#93a6b1;
             --accent:#4fb8d4; --accent-ink:#04202b; --border:#22414d; }
    }
    *{box-sizing:border-box}
    body{margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
      background:var(--bg); color:var(--ink); padding:24px;
      font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,sans-serif;}
    .err{background:var(--card); border:1px solid var(--border); border-radius:18px;
      box-shadow:0 20px 60px rgba(2,48,71,.12); padding:44px 40px; max-width:460px; width:100%;
      text-align:center;}
    .code{font-size:4.4rem; font-weight:800; line-height:1; letter-spacing:-.03em;
      background:linear-gradient(135deg,var(--accent),var(--ink)); -webkit-background-clip:text;
      background-clip:text; color:transparent;}
    h1{font-size:1.4rem; margin:.5em 0 .3em; letter-spacing:-.01em;}
    p{color:var(--muted); margin:0 auto 26px; max-width:34ch; line-height:1.55;}
    .btn{display:inline-block; background:var(--accent); color:var(--accent-ink);
      text-decoration:none; font-weight:600; padding:11px 22px; border-radius:10px;
      transition:transform .15s ease, box-shadow .15s ease;}
    .btn:hover{transform:translateY(-1px); box-shadow:0 8px 22px rgba(33,158,188,.35);}
    .btn:focus-visible{outline:3px solid var(--accent); outline-offset:2px;}
  </style>
</head>
<body>
  <main class="err">
    <div class="code"><?php echo (int) $err_code; ?></div>
    <h1><?php echo htmlspecialchars($err_title, ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars($err_msg, ENT_QUOTES, 'UTF-8'); ?></p>
    <a class="btn" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>index.php">Return to sign in</a>
  </main>
</body>
</html>
