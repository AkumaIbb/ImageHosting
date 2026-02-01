<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/cleanup.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/i18n.php';
require_once __DIR__ . '/../lib/layout.php';

ih_maybe_cleanup();
$lang = ih_get_language();
$translations = ih_i18n_payload($lang);

$cookieUserId = ih_get_user_id_cookie();
if (!is_admin($cookieUserId)) {
    header('Location: /admin_login.php', true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars(ih_t('admin.title', $lang), ENT_QUOTES, 'UTF-8'); ?> – ImageHosting</title>
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
      width: min(1300px, 100%);
      display: grid;
      gap: 24px;
    }
    header {
      display: grid;
      gap: 10px;
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
    .row {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }
    .input {
      background: rgba(9, 11, 18, 0.85);
      color: #eef2f8;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      min-width: 240px;
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
    .button.secondary {
      background: linear-gradient(135deg, #4f5b73, #30384a);
    }
    .grid {
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    }
    .thumb {
      background: rgba(8, 10, 16, 0.9);
      border-radius: 12px;
      padding: 10px;
      display: grid;
      gap: 10px;
    }
    .thumb img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 8px;
      background: rgba(0, 0, 0, 0.4);
    }
    .thumb small {
      color: #97a1b7;
    }
    .status {
      background: rgba(8, 10, 16, 0.9);
      border-radius: 12px;
      padding: 12px;
      font-size: 0.95rem;
      color: #c7f0ff;
    }
    .pagination {
      display: flex;
      gap: 12px;
      align-items: center;
      justify-content: center;
      margin-top: 8px;
    }
  </style>
</head>
<body>
<main>
  <?php ih_render_topbar($lang, $cookieUserId !== null, true); ?>
  <header>
    <h1><?php echo htmlspecialchars(ih_t('admin.title', $lang), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars(ih_t('admin.subtitle', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <section class="card">
    <h2><?php echo htmlspecialchars(ih_t('admin.filter_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div class="row">
      <input class="input" id="filterUserId" type="text" placeholder="<?php echo htmlspecialchars(ih_t('admin.filter_placeholder', $lang), ENT_QUOTES, 'UTF-8'); ?>">
      <button class="button secondary" id="applyFilter" type="button"><?php echo htmlspecialchars(ih_t('admin.filter_apply', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
      <button class="button secondary" id="clearFilter" type="button"><?php echo htmlspecialchars(ih_t('admin.filter_clear', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
    <div class="status" id="adminStatus"><?php echo htmlspecialchars(ih_t('admin.status_ready', $lang), ENT_QUOTES, 'UTF-8'); ?></div>
  </section>

  <section class="card">
    <h2><?php echo htmlspecialchars(ih_t('admin.default_ttl_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div class="row">
      <label for="defaultTtlHours"><?php echo htmlspecialchars(ih_t('admin.default_ttl_label', $lang), ENT_QUOTES, 'UTF-8'); ?></label>
      <input class="input" id="defaultTtlHours" type="number" min="0" step="1">
      <button class="button secondary" id="saveDefaultTtl" type="button"><?php echo htmlspecialchars(ih_t('admin.default_ttl_save', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
    <div class="status" id="defaultTtlStatus"><?php echo htmlspecialchars(ih_t('admin.default_ttl_status_loading', $lang), ENT_QUOTES, 'UTF-8'); ?></div>
  </section>

  <section class="card">
    <h2><?php echo htmlspecialchars(ih_t('admin.uploads_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div class="grid" id="uploadsGrid"></div>
    <div class="pagination">
      <button class="button secondary" id="prevPage" type="button"><?php echo htmlspecialchars(ih_t('admin.pagination_prev', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
      <span id="pageInfo"></span>
      <button class="button secondary" id="nextPage" type="button"><?php echo htmlspecialchars(ih_t('admin.pagination_next', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
  </section>
</main>

<script>
  window.IH_LANG = <?php echo json_encode($lang); ?>;
  window.IH_I18N = <?php echo json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<script src="/lang.js"></script>
<script>
  const i18n = window.IH_I18N || {};
  const t = (key, fallback) => i18n[key] || fallback || key;
  const format = (template, variables = {}) =>
    template.replace(/\{\{(\w+)\}\}/g, (_, token) =>
      Object.prototype.hasOwnProperty.call(variables, token) ? variables[token] : ''
    );

  const state = { page: 1, perPage: 24, total: 0, filterUserId: '' };
  const adminStatus = document.getElementById('adminStatus');
  const filterInput = document.getElementById('filterUserId');
  const uploadsGrid = document.getElementById('uploadsGrid');
  const prevPage = document.getElementById('prevPage');
  const nextPage = document.getElementById('nextPage');
  const pageInfo = document.getElementById('pageInfo');
  const defaultTtlInput = document.getElementById('defaultTtlHours');
  const saveDefaultTtl = document.getElementById('saveDefaultTtl');
  const defaultTtlStatus = document.getElementById('defaultTtlStatus');

  const fallbackImage =
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" width="320" height="200"><rect width="100%" height="100%" fill="#111723"/><text x="50%" y="50%" fill="#6c768f" font-family="Segoe UI, sans-serif" font-size="16" dominant-baseline="middle" text-anchor="middle">${t('account.no_preview', 'No preview')}</text></svg>`
    );

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, options);
    const data = await response.json();
    if (!response.ok || !data?.ok) {
      throw new Error(data?.error || 'Request failed');
    }
    return data;
  };

  const setStatus = (message) => {
    adminStatus.textContent = message;
  };

  const setDefaultTtlStatus = (message) => {
    defaultTtlStatus.textContent = message;
  };

  const updatePagination = () => {
    const totalPages = Math.max(1, Math.ceil(state.total / state.perPage));
    pageInfo.textContent = `${state.page} / ${totalPages}`;
    prevPage.disabled = state.page <= 1;
    nextPage.disabled = state.page >= totalPages;
  };

  const renderUploads = (items) => {
    uploadsGrid.innerHTML = '';
    if (!items.length) {
      uploadsGrid.innerHTML = `<p>${t('admin.no_uploads', 'No uploads found.')}</p>`;
      return;
    }
    items.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'thumb';
      const img = document.createElement('img');
      img.src = item.preview_url || fallbackImage;
      img.alt = t('admin.upload_preview_alt', 'Upload preview');
      const meta = document.createElement('small');
      meta.textContent = format(t('admin.upload_label', 'Upload {{id}} | User {{user}}'), {
        id: item.upload_id,
        user: item.user_id ?? t('common.anonymous', 'anonymous'),
      });
      const actions = document.createElement('div');
      actions.className = 'row';
      if (item.public_url) {
        const publicLink = document.createElement('a');
        publicLink.className = 'button secondary';
        publicLink.href = item.public_url;
        publicLink.textContent = t('common.public', 'Public');
        actions.appendChild(publicLink);
      }
      const manageLink = document.createElement('a');
      manageLink.className = 'button secondary';
      manageLink.href = item.manage_url;
      manageLink.textContent = t('common.manage', 'Manage');
      actions.appendChild(manageLink);

      const deleteButton = document.createElement('button');
      deleteButton.className = 'button secondary';
      deleteButton.textContent = t('common.delete', 'Delete');
      deleteButton.addEventListener('click', async () => {
        deleteButton.disabled = true;
        try {
          await requestJson('/api/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ upload_id: item.upload_id, delete_upload: true }),
          });
          loadUploads();
        } catch (error) {
          setStatus(t('admin.delete_failed', 'Delete failed.'));
          deleteButton.disabled = false;
        }
      });
      actions.appendChild(deleteButton);

      if (item.user_id) {
        const banButton = document.createElement('button');
        banButton.className = 'button secondary';
        banButton.textContent = item.is_banned
          ? t('admin.unban', 'Unban')
          : t('admin.ban', 'Ban');
        banButton.addEventListener('click', async () => {
          banButton.disabled = true;
          try {
            await requestJson('/api/admin_ban.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ user_id: item.user_id, banned: !item.is_banned }),
            });
            loadUploads();
          } catch (error) {
            setStatus(t('admin.ban_failed', 'Ban update failed.'));
            banButton.disabled = false;
          }
        });
        actions.appendChild(banButton);
      }

      card.appendChild(img);
      card.appendChild(meta);
      card.appendChild(actions);
      uploadsGrid.appendChild(card);
    });
  };

  const loadUploads = async () => {
    try {
      const filter = state.filterUserId ? `&user_id=${encodeURIComponent(state.filterUserId)}` : '';
      const data = await requestJson(`/api/admin_uploads.php?page=${state.page}&per_page=${state.perPage}${filter}`);
      state.total = data.total;
      renderUploads(data.items);
      updatePagination();
    } catch (error) {
      uploadsGrid.innerHTML = `<p>${t('admin.uploads_error', 'Uploads could not be loaded.')}</p>`;
    }
  };

  const loadDefaultTtl = async () => {
    try {
      setDefaultTtlStatus(t('admin.default_ttl_status_loading', 'Loading default TTL...'));
      const data = await requestJson('/api/admin_settings.php');
      defaultTtlInput.value = data.default_ttl_hours ?? 0;
      setDefaultTtlStatus(t('admin.default_ttl_status_ready', 'Default TTL loaded.'));
    } catch (error) {
      setDefaultTtlStatus(t('admin.default_ttl_status_failed', 'Default TTL could not be loaded.'));
    }
  };

  saveDefaultTtl.addEventListener('click', async () => {
    const hours = Number.parseInt(defaultTtlInput.value, 10);
    if (Number.isNaN(hours) || hours < 0) {
      setDefaultTtlStatus(t('admin.default_ttl_invalid', 'Please enter a valid number.'));
      return;
    }
    saveDefaultTtl.disabled = true;
    try {
      await requestJson('/api/admin_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ default_ttl_hours: hours }),
      });
      setDefaultTtlStatus(t('admin.default_ttl_saved', 'Default TTL saved.'));
    } catch (error) {
      setDefaultTtlStatus(t('admin.default_ttl_save_failed', 'Default TTL could not be saved.'));
    } finally {
      saveDefaultTtl.disabled = false;
    }
  });

  document.getElementById('applyFilter').addEventListener('click', () => {
    state.filterUserId = filterInput.value.trim();
    state.page = 1;
    loadUploads();
  });

  document.getElementById('clearFilter').addEventListener('click', () => {
    filterInput.value = '';
    state.filterUserId = '';
    state.page = 1;
    loadUploads();
  });

  prevPage.addEventListener('click', () => {
    if (state.page > 1) {
      state.page -= 1;
      loadUploads();
    }
  });

  nextPage.addEventListener('click', () => {
    state.page += 1;
    loadUploads();
  });

  loadDefaultTtl();
  loadUploads();
</script>
</body>
</html>
