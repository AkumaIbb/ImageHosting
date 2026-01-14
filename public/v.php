<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/cleanup.php';
require_once __DIR__ . '/../lib/uploads.php';
require_once __DIR__ . '/../lib/shortcodes.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/i18n.php';
require_once __DIR__ . '/../lib/layout.php';

ih_maybe_cleanup();
$lang = ih_get_language();
$translations = ih_i18n_payload($lang);

$code = (string)($_GET['id'] ?? '');
$uploadId = null;
if (short_is_valid_code($code)) {
  short_purge_expired();
  $row = short_resolve($code);
  if ($row && ($row['expires_at'] ?? 0) >= time()) {
    $uploadId = ih_sanitize_id($row['upload_id'] ?? null);
  }
}

$upload = $uploadId ? ih_load_upload($uploadId) : null;
$isMissing = !$upload;
$cookieUserId = ih_get_user_id_cookie();
$isLoggedIn = $cookieUserId !== null;
$isAdmin = $cookieUserId ? is_admin($cookieUserId) : false;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars(ih_t('gallery.title', $lang), ENT_QUOTES, 'UTF-8'); ?> – ImageHosting</title>
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
      padding: 40px 16px;
      display: flex;
      justify-content: center;
    }
    main {
      width: min(1100px, 100%);
      display: grid;
      gap: 24px;
    }
    header h1 {
      font-size: clamp(2rem, 4vw, 3rem);
    }
    header p {
      color: #c4cad8;
      margin-top: 8px;
    }
    .card {
      background: rgba(13, 16, 24, 0.72);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 22px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
    }
    .single img {
      width: 100%;
      border-radius: 12px;
      max-height: 520px;
      object-fit: contain;
      background: rgba(0, 0, 0, 0.4);
    }
    .grid {
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    .thumb {
      background: rgba(8, 10, 16, 0.9);
      border-radius: 12px;
      padding: 10px;
      display: grid;
      gap: 8px;
      justify-items: center;
    }
    .thumb img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 8px;
    }
    .thumb a {
      color: #8ad1ff;
      font-size: 0.9rem;
      text-decoration: none;
    }
    footer {
      text-align: center;
      color: #97a1b7;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<main>
  <?php ih_render_topbar($lang, $isLoggedIn, $isAdmin); ?>
  <header>
    <h1><?php echo htmlspecialchars(ih_t('gallery.title', $lang), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars(ih_t('gallery.subtitle', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <?php if ($isMissing): ?>
    <section class="card">
      <h2><?php echo htmlspecialchars(ih_t('gallery.missing_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(ih_t('gallery.missing_detail', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>
  <?php elseif ($upload['type'] === 'single'): ?>
    <section class="card single">
      <?php $file = $upload['files'][0]; ?>
      <img src="<?php echo ih_public_file_url($uploadId, $file['filename']); ?>" alt="<?php echo htmlspecialchars(ih_t('gallery.image_alt', $lang), ENT_QUOTES, 'UTF-8'); ?>">
    </section>
  <?php else: ?>
    <section class="card">
      <div class="grid">
        <?php foreach ($upload['files'] as $file): ?>
          <div class="thumb">
            <img src="<?php echo ih_public_file_url($uploadId, $file['filename']); ?>" alt="<?php echo htmlspecialchars(ih_t('gallery.image_alt', $lang), ENT_QUOTES, 'UTF-8'); ?>">
            <a href="<?php echo ih_public_file_url($uploadId, $file['filename']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(ih_t('gallery.direct_link', $lang), ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

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
</body>
</html>
