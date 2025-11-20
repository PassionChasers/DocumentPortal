<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Access Restricted</title>

  <!-- fav icons -->
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/img/hdc.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/img/hdc.png">

  <style>
    :root{
      --bg: #f5f7fa;
      --surface: #ffffff;
      --text: #0f1724;
      --muted: #6b7280;
      --accent: #d64545; /* red tone for access denied */
      --radius: 10px;
      --shadow: 0 8px 28px rgba(12,18,30,0.06);
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
      max-width:520px;
      background:var(--surface);
      border-radius:var(--radius);
      padding:36px;
      box-shadow:var(--shadow);
      text-align:left;
      border-left:4px solid rgba(214,69,69,0.10);
    }

    .eyebrow{
      display:inline-block;
      font-size:13px;
      font-weight:600;
      color:var(--accent);
      margin-bottom:14px;
      letter-spacing:0.2px;
    }

    h1{
      font-size:28px;
      font-weight:600;
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
      background:#fff;
      color:var(--text);
    }

    .btn-primary{
      background:var(--accent);
      color:white;
      border:0;
      box-shadow:0 8px 20px rgba(214,69,69,0.12);
    }

    .btn-muted{
      background:#fff;
      color:var(--muted);
      border:1px solid rgba(12,18,30,0.10);
    }

    .hint{
      font-size:13px;
      color:var(--muted);
      margin-top:10px;
    }

  </style>
</head>

<body>

  <section class="panel">
    <div class="eyebrow">Access Restricted</div>

    <h1>Access Denied</h1>

    <p class="lead">
      You do not have permission to view this directory or resource.  
      This area may be restricted, protected, or intended for system-level use only.
    </p>

    <div class="actions">
      <a href="/" class="btn btn-primary">Return Back</a>
      <a href="/support" class="btn btn-muted">Contact support</a>
    </div>

    <p class="hint">
      If you believe this is an error, please contact your administrator or support team.
    </p>
  </section>

</body>
</html>
