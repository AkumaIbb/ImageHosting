<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/cleanup.php';
require_once __DIR__ . '/../lib/uploads.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/i18n.php';
require_once __DIR__ . '/../lib/layout.php';

ih_maybe_cleanup();
$lang = ih_get_language();
$translations = ih_i18n_payload($lang);

$uploadId = ih_sanitize_id($_GET['id'] ?? null);
$upload = $uploadId ? ih_load_upload($uploadId) : null;
$shortCode = is_array($upload) ? ($upload['short_code'] ?? null) : null;
$publicUrl = $shortCode ? '/v.php?id=' . $shortCode : null;
$isMissing = !$upload;
$cookieUserId = ih_get_user_id_cookie();
$isAdmin = is_admin($cookieUserId);
$isLoggedIn = $cookieUserId !== null;
$ownerId = is_array($upload) ? ($upload['user_id'] ?? null) : null;
$isAuthorized = !$ownerId || $isAdmin || ($cookieUserId && $ownerId === $cookieUserId);
$expiresAt = is_array($upload) ? ($upload['expires_at'] ?? null) : null;
if ($expiresAt !== null) {
  $expiresAt = (int)$expiresAt;
}
$expiresLabel = ih_t('manage.expires_default', $lang);
if ($expiresAt === null && !$isMissing) {
  $expiresLabel = ih_t('manage.expires_unlimited', $lang);
} elseif (is_int($expiresAt) && !$isMissing) {
  $expiresLabel = str_replace(
      '{{date}}',
      date('Y-m-d H:i', $expiresAt),
      ih_t('manage.expires_at', $lang)
  );
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars(ih_t('manage.title', $lang), ENT_QUOTES, 'UTF-8'); ?> – ImageHosting</title>
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
    header {
      display: grid;
      gap: 8px;
    }
    header h1 {
      font-size: clamp(2rem, 4vw, 3rem);
    }
    header p {
      color: #c4cad8;
    }
    .card {
      background: rgba(13, 16, 24, 0.72);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 22px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
      display: grid;
      gap: 16px;
    }
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
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
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      box-shadow: 0 10px 20px rgba(10, 20, 20, 0.4);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .button.secondary {
      background: linear-gradient(135deg, #4f5b73, #30384a);
    }
    .dropzone {
      border: 2px dashed rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 24px;
      display: grid;
      gap: 12px;
      justify-items: center;
      text-align: center;
    }
    .dropzone.is-active {
      border-color: #7cd4ff;
      background: rgba(124, 212, 255, 0.08);
    }
    .dropzone.is-loading {
      border-color: #3fb47a;
      background: rgba(63, 180, 122, 0.12);
    }
    .dropzone input[type="file"] {
      display: none;
    }
    .input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(9, 11, 18, 0.85);
      color: #eef2f8;
    }
    .status {
      background: rgba(8, 10, 16, 0.9);
      border-radius: 12px;
      padding: 12px;
      font-size: 0.95rem;
      color: #c7f0ff;
    }
    .grid {
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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
      height: 140px;
      object-fit: cover;
      border-radius: 8px;
    }
    .thumb small {
      color: #97a1b7;
      font-size: 0.85rem;
      text-align: center;
      word-break: break-all;
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
    <h1><?php echo htmlspecialchars(ih_t('manage.title', $lang), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars(ih_t('manage.subtitle', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <?php if ($isMissing): ?>
    <section class="card">
      <h2><?php echo htmlspecialchars(ih_t('manage.missing_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(ih_t('manage.missing_detail', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
      <a class="button" href="/"><?php echo htmlspecialchars(ih_t('manage.back_home', $lang), ENT_QUOTES, 'UTF-8'); ?></a>
    </section>
  <?php else: ?>
    <section class="card">
      <h2><?php echo htmlspecialchars($upload['type'] === 'album' ? ih_t('manage.upload_album', $lang) : ih_t('manage.upload_single', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
      <div class="actions">
        <?php if ($publicUrl): ?>
          <a class="button secondary" href="<?php echo $publicUrl; ?>"><?php echo htmlspecialchars(ih_t('manage.public_view', $lang), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php else: ?>
          <span class="button secondary" aria-disabled="true"><?php echo htmlspecialchars(ih_t('manage.public_unavailable', $lang), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if ($isAuthorized): ?>
          <button class="button" id="addButton" type="button"><?php echo htmlspecialchars(ih_t('manage.add_images', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
        <?php endif; ?>
      </div>
      <?php if ($isAuthorized): ?>
        <div class="dropzone" id="dropzone">
          <p><?php echo htmlspecialchars(ih_t('manage.drop_hint', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
          <label class="button secondary" for="fileInput"><?php echo htmlspecialchars(ih_t('manage.select_images', $lang), ENT_QUOTES, 'UTF-8'); ?></label>
          <input id="fileInput" type="file" accept="image/*" multiple>
          <input id="fileName" type="text" class="input" readonly placeholder="<?php echo htmlspecialchars(ih_t('manage.file_placeholder', $lang), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      <?php else: ?>
        <div class="status"><?php echo htmlspecialchars(ih_t('manage.not_authorized', $lang), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <div class="status" id="status"><?php echo htmlspecialchars(ih_t('manage.status_ready', $lang), ENT_QUOTES, 'UTF-8'); ?></div>
    </section>

    <section class="card">
      <h2><?php echo htmlspecialchars(ih_t('manage.contents_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
      <div class="grid">
        <?php foreach ($upload['files'] as $file): ?>
          <div class="thumb">
            <img src="<?php echo ih_public_file_url($uploadId, $file['filename']); ?>" alt="<?php echo htmlspecialchars(ih_t('manage.image_alt', $lang), ENT_QUOTES, 'UTF-8'); ?>">
            <small><?php echo htmlspecialchars($file['original'], ENT_QUOTES); ?></small>
            <?php if ($isAuthorized): ?>
              <button class="button secondary delete-button" data-file-id="<?php echo $file['id']; ?>" type="button"><?php echo htmlspecialchars(ih_t('manage.delete_image', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <footer><?php echo htmlspecialchars($expiresLabel, ENT_QUOTES); ?></footer>
</main>

<script>
  window.IH_LANG = <?php echo json_encode($lang); ?>;
  window.IH_I18N = <?php echo json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<script src="/lang.js"></script>

<?php if (!$isMissing && $isAuthorized): ?>
<script>
  const i18n = window.IH_I18N || {};
  const t = (key, fallback) => i18n[key] || fallback || key;
  const format = (template, variables = {}) =>
    template.replace(/\{\{(\w+)\}\}/g, (_, token) =>
      Object.prototype.hasOwnProperty.call(variables, token) ? variables[token] : ''
    );

  const uploadId = <?php echo json_encode($uploadId); ?>;
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const fileName = document.getElementById('fileName');
  const addButton = document.getElementById('addButton');
  const statusBox = document.getElementById('status');

  const state = { files: [], isUploading: false };

  const renderStatus = (message) => {
    statusBox.textContent = message;
  };

  const updateFileDisplay = () => {
    if (state.files.length === 0) {
      fileName.value = t('manage.files_none', 'No files selected');
      return;
    }
    fileName.value = format(t('manage.files_selected', '{{count}} files selected'), {
      count: state.files.length,
    });
  };

  const addFiles = (files) => {
    const images = Array.from(files).filter((file) => file.type.startsWith('image/'));
    if (images.length === 0) {
      renderStatus(t('manage.only_images', 'Please add image files only.'));
      return;
    }
    state.files = images;
    updateFileDisplay();
    renderStatus(
      format(t('manage.images_ready', '{{count}} image(s) ready to upload.'), {
        count: images.length,
      })
    );
  };

  ['dragenter', 'dragover'].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      if (state.isUploading) {
        return;
      }
      dropzone.classList.add('is-active');
    });
  });

  ['dragleave', 'drop'].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.remove('is-active');
    });
  });

  dropzone.addEventListener('drop', (event) => {
    if (state.isUploading) {
      return;
    }
    addFiles(event.dataTransfer.files);
  });

  fileInput.addEventListener('change', (event) => {
    if (state.isUploading) {
      return;
    }
    addFiles(event.target.files);
  });

  window.addEventListener('paste', (event) => {
    if (state.isUploading) {
      return;
    }
    const files = event.clipboardData?.files ?? [];
    if (files.length > 0) {
      addFiles(files);
    }
  });

  const uploadFiles = async () => {
    if (state.files.length === 0) {
      renderStatus(t('manage.select_images_first', 'Please select images first.'));
      return;
    }
    const formData = new FormData();
    state.files.forEach((file) => {
      formData.append('files[]', file);
    });
    formData.append('upload_id', uploadId);

    state.isUploading = true;
    dropzone.classList.add('is-loading');
    renderStatus(t('manage.upload_running', 'Upload in progress ...'));

    try {
      const response = await fetch('/api/upload.php', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Upload fehlgeschlagen.');
      }
      renderStatus(t('manage.upload_done', 'Upload complete. Reloading ...'));
      window.location.reload();
    } catch (error) {
      renderStatus(error.message);
    } finally {
      state.isUploading = false;
      dropzone.classList.remove('is-loading');
    }
  };

  addButton.addEventListener('click', uploadFiles);

  document.querySelectorAll('.delete-button').forEach((button) => {
    button.addEventListener('click', async () => {
      const fileId = button.dataset.fileId;
      if (!fileId) {
        return;
      }
      renderStatus(t('manage.delete_running', 'Deleting ...'));
      try {
        const response = await fetch('/api/delete.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ upload_id: uploadId, file_id: fileId }),
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
          throw new Error(data.error || 'Löschen fehlgeschlagen.');
        }
        window.location.reload();
      } catch (error) {
        renderStatus(error.message === 'Löschen fehlgeschlagen.' ? t('manage.delete_failed', 'Delete failed.') : error.message);
      }
    });
  });
</script>
<?php endif; ?>
</body>
</html>
