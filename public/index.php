<?php
require_once __DIR__ . '/../lib/cleanup.php';
require_once __DIR__ . '/../lib/shortcodes.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/i18n.php';
require_once __DIR__ . '/../lib/layout.php';

ih_maybe_cleanup();
$lang = ih_get_language();
$translations = ih_i18n_payload($lang);
$cookieUserId = ih_get_user_id_cookie();
$isLoggedIn = $cookieUserId !== null;
$isAdmin = $cookieUserId ? is_admin($cookieUserId) : false;

if (isset($_GET['id'])) {
  $code = (string)$_GET['id'];
  if (!short_is_valid_code($code)) {
      http_response_code(404);
      echo 'Link existiert nicht oder ist abgelaufen.';
      exit;
  }

  short_purge_expired();
  $row = short_resolve($code);
  if (!$row || ($row['expires_at'] ?? 0) < time()) {
      http_response_code(404);
      echo 'Link existiert nicht oder ist abgelaufen.';
      exit;
  }

  header('Location: /v.php?id=' . rawurlencode($code), true, 302);
  exit;
}
?><!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ImageHosting</title>
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
      align-items: center;
      padding: 40px 16px;
    }

    .shell {
      width: min(1100px, 100%);
      display: grid;
      gap: 28px;
    }

    header {
      text-align: center;
    }

    header h1 {
      font-size: clamp(2.2rem, 4vw, 3.4rem);
      letter-spacing: 0.04em;
      text-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    header p {
      margin-top: 12px;
      color: #c4cad8;
      font-size: 1.1rem;
    }

    .card {
      background: rgba(13, 16, 24, 0.72);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 22px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
    }

    .card h2 {
      font-size: 1.4rem;
      margin-bottom: 12px;
    }

    .dropzone {
      border: 2px dashed rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 32px 24px;
      display: grid;
      gap: 14px;
      justify-items: center;
      text-align: center;
      transition: border-color 0.2s ease, background 0.2s ease;
    }

    .dropzone.is-active {
      border-color: #7cd4ff;
      background: rgba(124, 212, 255, 0.08);
    }

    .dropzone.is-loading {
      border-color: #3fb47a;
      background: rgba(63, 180, 122, 0.12);
    }

    .dropzone__icon {
      width: 110px;
      height: 90px;
      border: 6px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      position: relative;
      transform: rotate(-3deg);
    }

    .dropzone__icon::after {
      content: "";
      position: absolute;
      inset: 12px;
      border: 5px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
    }

    .dropzone__hint {
      color: #c0c7d6;
      font-size: 1rem;
    }

    .controls {
      display: grid;
      gap: 12px;
      width: 100%;
    }

    .controls input[type="file"] {
      display: none;
    }

    .button,
    button,
    .input {
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      font-size: 1rem;
    }

    .button,
    button {
      background: linear-gradient(135deg, #3fb47a, #2e7f5c);
      color: #f6fbf9;
      padding: 12px 16px;
      cursor: pointer;
      font-weight: 600;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      box-shadow: 0 10px 20px rgba(10, 20, 20, 0.4);
    }

    button.secondary {
      background: linear-gradient(135deg, #4f5b73, #30384a);
    }

    .button:hover,
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(10, 20, 20, 0.45);
    }

    .input,
    input[type="text"] {
      background: rgba(9, 11, 18, 0.85);
      color: #eef2f8;
      padding: 12px 14px;
      width: 100%;
    }

    .status {
      width: 100%;
      background: rgba(8, 10, 16, 0.9);
      border-radius: 12px;
      padding: 14px;
      font-size: 0.95rem;
      color: #c7f0ff;
      min-height: 64px;
      display: grid;
      gap: 6px;
    }

    .status small {
      color: #97a1b7;
    }

    .spinner {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 3px solid rgba(255, 255, 255, 0.2);
      border-top-color: #7cd4ff;
      animation: spin 0.9s linear infinite;
      display: none;
    }

    .dropzone.is-loading .spinner {
      display: block;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    footer {
      text-align: center;
      color: #97a1b7;
      font-size: 0.95rem;
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 12px;
    }
  </style>
</head>
<body>
  <main class="shell">
    <?php ih_render_topbar($lang, $isLoggedIn, $isAdmin); ?>
    <header>
      <h1><?php echo htmlspecialchars(ih_t('index.hero_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h1>
      <p><?php echo htmlspecialchars(ih_t('index.hero_subtitle', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
    </header>

    <section class="card">
      <div class="dropzone" id="dropzone">
        <div class="dropzone__icon" aria-hidden="true"></div>
        <div>
          <h2><?php echo htmlspecialchars(ih_t('index.drop_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
          <p class="dropzone__hint"><?php echo htmlspecialchars(ih_t('index.drop_hint', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="spinner" aria-hidden="true"></div>
        <div class="controls">
          <label class="button" for="fileInput"><?php echo htmlspecialchars(ih_t('index.select_images', $lang), ENT_QUOTES, 'UTF-8'); ?></label>
          <input id="fileInput" type="file" accept="image/*" multiple>
          <input id="fileName" class="input" type="text" placeholder="<?php echo htmlspecialchars(ih_t('app.files_none', $lang), ENT_QUOTES, 'UTF-8'); ?>" readonly>
          <button id="uploadButton"><?php echo htmlspecialchars(ih_t('index.upload_start', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
      </div>
      <div class="status" id="uploadStatus">
        <strong><?php echo htmlspecialchars(ih_t('index.status_ready_title', $lang), ENT_QUOTES, 'UTF-8'); ?></strong>
        <small><?php echo htmlspecialchars(ih_t('index.status_ready_detail', $lang), ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
    </section>

    <section class="card" id="accountCard">
      <h2><?php echo htmlspecialchars(ih_t('index.account_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(ih_t('index.account_desc', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
      <div class="actions">
        <button class="button secondary" id="registerButton" type="button"><?php echo htmlspecialchars(ih_t('index.account_create', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
        <a class="button secondary" id="accountLink" href="/account.php" style="display: none;"><?php echo htmlspecialchars(ih_t('index.account_uploads', $lang), ENT_QUOTES, 'UTF-8'); ?></a>
        <a class="button secondary" id="adminLink" href="/admin.php" style="display: none;"><?php echo htmlspecialchars(ih_t('index.account_admin', $lang), ENT_QUOTES, 'UTF-8'); ?></a>
      </div>
      <div class="status" id="accountStatus">
        <strong><?php echo htmlspecialchars(ih_t('index.account_status_title', $lang), ENT_QUOTES, 'UTF-8'); ?></strong>
        <small><?php echo htmlspecialchars(ih_t('index.account_status_detail', $lang), ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
    </section>

    <footer>
      <p><?php echo htmlspecialchars(ih_t('footer.disclaimer', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(ih_t('footer.rules', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(ih_t('footer.as_is', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
    </footer>
  </main>

  <script>
    window.IH_LANG = <?php echo json_encode($lang); ?>;
    window.IH_I18N = <?php echo json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  </script>
  <script src="/lang.js"></script>
  <script src="/app.js"></script>
</body>
</html>
