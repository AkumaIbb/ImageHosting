<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/logger.php';

$cookieUserId = ih_get_user_id_cookie();
if (!$cookieUserId) {
    http_response_code(403);
    echo 'Nicht autorisiert.';
    exit;
}

if (is_admin($cookieUserId)) {
    header('Location: /admin.php', true, 302);
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim((string)($_POST['admin_token'] ?? ''));
    if ($token === '') {
        $error = 'Login fehlgeschlagen.';
    } elseif (!ih_admin_login($cookieUserId, $token)) {
        log_msg('warning', 'admin login failed', [
            'user_id' => $cookieUserId,
        ]);
        $error = 'Login fehlgeschlagen.';
    } else {
        log_msg('info', 'admin login success', [
            'user_id' => $cookieUserId,
        ]);
        header('Location: /admin.php', true, 302);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login – ImageHosting</title>
  <style>
    :root {
      color-scheme: dark;
      font-family: "Segoe UI", "Inter", system-ui, -apple-system, sans-serif;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      min-height: 100vh;
      background: radial-gradient(circle at top, #3a3f4e 0%, #1c1f28 45%, #131620 100%);
      color: #eef2f8;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 16px;
    }
    .card {
      width: min(520px, 100%);
      background: rgba(13, 16, 24, 0.72);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
      display: grid;
      gap: 16px;
    }
    h1 {
      font-size: clamp(1.8rem, 4vw, 2.4rem);
    }
    p {
      color: #c4cad8;
    }
    .input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(9, 11, 18, 0.85);
      color: #eef2f8;
    }
    .button {
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      font-size: 1rem;
      background: linear-gradient(135deg, #3fb47a, #2e7f5c);
      color: #f6fbf9;
      padding: 10px 16px;
      cursor: pointer;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .status {
      border-radius: 12px;
      padding: 12px;
      background: rgba(8, 10, 16, 0.9);
      color: #c7f0ff;
      font-size: 0.95rem;
    }
    .status.error {
      color: #ffb3b3;
    }
  </style>
</head>
<body>
  <main class="card">
    <h1>Admin Login</h1>
    <p>Bitte Admin-Token eingeben, um den Adminbereich zu öffnen.</p>
    <?php if ($error): ?>
      <div class="status error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post">
      <input class="input" type="password" name="admin_token" placeholder="Admin Token" autocomplete="current-password" required>
      <div style="height: 12px;"></div>
      <button class="button" type="submit">Anmelden</button>
    </form>
  </main>
</body>
</html>
