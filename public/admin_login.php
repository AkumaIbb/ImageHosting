<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/logger.php';
require_once __DIR__ . '/../lib/i18n.php';
require_once __DIR__ . '/../lib/layout.php';

$lang = ih_get_language();
$translations = ih_i18n_payload($lang);

$cookieUserId = ih_get_user_id_cookie();
if (!$cookieUserId) {
    http_response_code(403);
    echo htmlspecialchars(ih_t('admin_login.unauthorized', $lang), ENT_QUOTES, 'UTF-8');
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
        $error = ih_t('admin_login.error', $lang);
    } elseif (!ih_admin_login($cookieUserId, $token)) {
        log_msg('warning', 'admin login failed', [
            'user_id' => $cookieUserId,
        ]);
        $error = ih_t('admin_login.error', $lang);
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
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars(ih_t('admin_login.title', $lang), ENT_QUOTES, 'UTF-8'); ?> – ImageHosting</title>
  <link rel="stylesheet" href="/shared.css">
  <style>
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
      padding: 40px 16px;
    }
    .page {
      width: min(520px, 100%);
      display: grid;
      gap: 20px;
    }
    .card {
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
  <div class="page">
    <?php ih_render_topbar($lang, true, false); ?>
    <main class="card">
      <h1><?php echo htmlspecialchars(ih_t('admin_login.title', $lang), ENT_QUOTES, 'UTF-8'); ?></h1>
      <p><?php echo htmlspecialchars(ih_t('admin_login.subtitle', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
      <?php if ($error): ?>
        <div class="status error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <form method="post">
        <input class="input" type="password" name="admin_token" placeholder="<?php echo htmlspecialchars(ih_t('admin_login.placeholder', $lang), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="current-password" required>
        <div style="height: 12px;"></div>
        <button class="button" type="submit"><?php echo htmlspecialchars(ih_t('admin_login.button', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
      </form>
    </main>
  </div>

  <script>
    window.IH_LANG = <?php echo json_encode($lang); ?>;
    window.IH_I18N = <?php echo json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  </script>
  <script src="/lang.js"></script>
</body>
</html>
