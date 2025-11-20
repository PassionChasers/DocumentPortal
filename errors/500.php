<?php
// 500.php
// A simple, professional 500 (Internal Server Error) page.
// Place this file on your server. If you want to display a Request ID,
// set $request_id before including or rendering this file, e.g.:
//   $request_id = $_SERVER['REQUEST_ID'] ?? null;
// Optionally set $show_debug = true in development to reveal debug details.

$request_id = $request_id ?? ($_SERVER['REQUEST_ID'] ?? null);
$show_debug = $show_debug ?? false;
$timestamp = date('Y-m-d H:i:s');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>500 — Internal Server Error</title>

  <!-- fav icons (adjust paths as necessary) -->
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/img/hdc.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/img/hdc.png">

  <style>
    :root{
      --bg: #f5f7fa;
      --surface: #ffffff;
      --text: #0f1724;
      --muted: #6b7280;
      --accent: #d64545; /* error red accent */
      --radius: 10px;
      --shadow: 0 10px 30px rgba(12,18,30,0.06);
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(180deg,var(--bg),#fff 60%);
      color:var(--text);
      padding:20px;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    .panel{
      width:100%;
      max-width:640px;
      background:var(--surface);
      border-radius:var(--radius);
      padding:36px;
      box-shadow:var(--shadow);
      text-align:left;
      border-left:4px solid rgba(214,69,69,0.06);
    }

    .eyebrow{
      display:inline-block;
      font-size:13px;
      font-weight:700;
      color:var(--accent);
      margin-bottom:14px;
      letter-spacing:0.2px;
    }

    h1{
      font-size:28px;
      font-weight:700;
      margin-bottom:8px;
    }

    p.lead{
      color:var(--muted);
      font-size:15px;
      line-height:1.6;
      margin-bottom:22px;
    }

    .actions{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:18px;
    }

    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:10px 14px;
      border-radius:8px;
      font-weight:600;
      font-size:14px;
      text-decoration:none;
      border:1px solid rgba(12,18,30,0.06);
      cursor:pointer;
      color:var(--text);
      background:#fff;
    }

    .btn-primary{
      background:var(--accent);
      color:white;
      border:0;
      box-shadow:0 8px 20px rgba(214,69,69,0.10);
    }

    .btn-muted{
      background:transparent;
      color:var(--muted);
      border:1px solid rgba(12,18,30,0.06);
    }

    .info{
      display:flex;
      gap:18px;
      flex-wrap:wrap;
      margin-bottom:12px;
      align-items:center;
    }

    .info .meta{
      background:#fff5f5;
      color:var(--muted);
      padding:10px 12px;
      border-radius:8px;
      font-size:13px;
      border:1px solid rgba(214,69,69,0.06);
    }

    .label{
      font-size:13px;
      color:var(--muted);
    }

    .request-id{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, "Roboto Mono", monospace;
      font-size:13px;
      color:#7b1d1d;
      background:#fff2f2;
      padding:6px 8px;
      border-radius:6px;
      border:1px solid rgba(214,69,69,0.06);
    }

    .hint{
      font-size:13px;
      color:var(--muted);
      margin-top:10px;
    }

    .debug{
      margin-top:18px;
      background:#f8f8f9;
      border-radius:8px;
      padding:12px;
      font-family: ui-monospace, Menlo, monospace;
      font-size:13px;
      color:#222;
      border:1px solid rgba(12,18,30,0.03);
      white-space:pre-wrap;
    }

    @media (max-width:640px){
      .panel{padding:24px}
      h1{font-size:22px}
    }
  </style>
</head>
<body>
  <section class="panel" role="main" aria-labelledby="title">
    <div class="eyebrow">Document Portal</div>

    <h1 id="title">500 — Internal server error</h1>

    <p class="lead">
      Something went wrong while processing your request. Our team has been notified.
      You can try again, or contact support if the error persists.
    </p>

    <div class="actions" role="group" aria-label="Primary actions">
      <button type="button" class="btn btn-primary" onclick="location.reload();">Try again</button>
      <a href="/status" class="btn btn-muted">System status</a>
      <a href="/support" class="btn">Contact support</a>
    </div>

    <div class="info" aria-hidden="false">
      <div class="meta">
        <div class="label">Time:</div>
        <div class="request-id"><?php echo htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <?php if ($request_id): ?>
      <div class="meta" style="display:flex;align-items:center;">
        <div class="label" style="margin-right:8px">Request ID:</div>
        <div class="request-id"><?php echo htmlspecialchars($request_id, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <?php endif; ?>
    </div>

    <p class="hint">
      When contacting support please include the Request ID (if shown) and the approximate time.
    </p>

    <?php if ($show_debug && !empty($debug_info)): ?>
      <!-- debug_info should be prepared server-side only in development -->
      <div class="debug" role="status">
        <?php echo htmlspecialchars($debug_info, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

  </section>
</body>
</html>
