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
$isLoggedIn = $cookieUserId !== null;
$isAdmin = $cookieUserId ? is_admin($cookieUserId) : false;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars(ih_t('account.title', $lang), ENT_QUOTES, 'UTF-8'); ?> – ImageHosting</title>
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
      width: min(1200px, 100%);
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
      gap: 16px;
      align-items: center;
    }
    .input,
    select {
      background: rgba(9, 11, 18, 0.85);
      color: #eef2f8;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      min-width: 220px;
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
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
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
  <?php ih_render_topbar($lang, $isLoggedIn, $isAdmin); ?>
  <header>
    <h1><?php echo htmlspecialchars(ih_t('account.title', $lang), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars(ih_t('account.subtitle', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <section class="card" id="accountCard">
    <h2><?php echo htmlspecialchars(ih_t('account.key_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div class="row">
      <input class="input" id="userId" type="text" readonly placeholder="<?php echo htmlspecialchars(ih_t('account.key_placeholder', $lang), ENT_QUOTES, 'UTF-8'); ?>">
      <button class="button secondary" id="copyUserId" type="button"><?php echo htmlspecialchars(ih_t('account.copy_key', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
    <p><?php echo htmlspecialchars(ih_t('account.key_desc', $lang), ENT_QUOTES, 'UTF-8'); ?></p>
    <div class="row">
      <label for="ttlSelect"><?php echo htmlspecialchars(ih_t('account.ttl_label', $lang), ENT_QUOTES, 'UTF-8'); ?></label>
      <select id="ttlSelect"></select>
      <button class="button secondary" id="saveTtl" type="button"><?php echo htmlspecialchars(ih_t('account.ttl_save', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
    <div class="status" id="accountStatus"><?php echo htmlspecialchars(ih_t('account.status_loading', $lang), ENT_QUOTES, 'UTF-8'); ?></div>
  </section>

  <section class="card">
    <h2><?php echo htmlspecialchars(ih_t('account.uploads_title', $lang), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div class="grid" id="uploadsGrid"></div>
    <div class="pagination">
      <button class="button secondary" id="prevPage" type="button"><?php echo htmlspecialchars(ih_t('account.pagination_prev', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
      <span id="pageInfo"></span>
      <button class="button secondary" id="nextPage" type="button"><?php echo htmlspecialchars(ih_t('account.pagination_next', $lang), ENT_QUOTES, 'UTF-8'); ?></button>
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

  const state = { page: 1, perPage: 24, total: 0, userId: null, isBanned: false, isAdmin: false };

  const accountStatus = document.getElementById('accountStatus');
  const userIdInput = document.getElementById('userId');
  const copyButton = document.getElementById('copyUserId');
  const ttlSelect = document.getElementById('ttlSelect');
  const saveTtl = document.getElementById('saveTtl');
  const uploadsGrid = document.getElementById('uploadsGrid');
  const prevPage = document.getElementById('prevPage');
  const nextPage = document.getElementById('nextPage');
  const pageInfo = document.getElementById('pageInfo');

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, options);
    const data = await response.json();
    if (!response.ok || !data?.ok) {
      throw new Error(data?.error || 'Request failed');
    }
    return data;
  };

  const setAccountStatus = (message) => {
    accountStatus.textContent = message;
  };

  const renderTtlOptions = (options, currentSeconds) => {
    ttlSelect.innerHTML = '';
    options.forEach((option) => {
      const item = document.createElement('option');
      item.value = option.seconds === null ? 'unlimited' : option.seconds;
      item.textContent =
        option.label === 'unlimited' ? t('account.ttl_unlimited', 'Unlimited') : option.label;
      if (option.seconds === currentSeconds || (option.seconds === null && currentSeconds === null)) {
        item.selected = true;
      }
      ttlSelect.appendChild(item);
    });
  };

  const loadAccount = async () => {
    try {
      const data = await requestJson('/api/me.php');
      if (!data.user_id) {
        setAccountStatus(t('account.status_no_account', 'No account found.'));
        return;
      }
      state.userId = data.user_id;
      state.isBanned = data.is_banned;
      state.isAdmin = data.is_admin;
      userIdInput.value = data.user_id;
      renderTtlOptions(data.ttl_options, data.ttl_seconds);
      setAccountStatus(
        data.is_banned ? t('account.status_banned', 'Account is banned.') : t('account.status_active', 'Account active.')
      );
      copyButton.disabled = !data.user_id;
      ttlSelect.disabled = data.is_banned;
      saveTtl.disabled = data.is_banned;
    } catch (error) {
      setAccountStatus(t('account.status_load_error', 'Account data could not be loaded.'));
    }
  };

  const fallbackImage =
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" width="320" height="200"><rect width="100%" height="100%" fill="#111723"/><text x="50%" y="50%" fill="#6c768f" font-family="Segoe UI, sans-serif" font-size="16" dominant-baseline="middle" text-anchor="middle">${t('account.no_preview', 'No preview')}</text></svg>`
    );

  const renderUploads = (items) => {
    uploadsGrid.innerHTML = '';
    if (!items.length) {
      uploadsGrid.innerHTML = `<p>${t('account.no_uploads', 'No uploads available.')}</p>`;
      return;
    }
    items.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'thumb';
      const img = document.createElement('img');
      img.src = item.preview_url || fallbackImage;
      img.alt = t('account.upload_preview_alt', 'Upload preview');
      const meta = document.createElement('small');
      meta.textContent = format(t('account.upload_label', 'Upload {{id}}'), { id: item.upload_id });
      const actions = document.createElement('div');
      actions.className = 'actions';
      if (item.public_url) {
        const publicLink = document.createElement('a');
        publicLink.className = 'button secondary';
        publicLink.href = item.public_url;
        publicLink.textContent = t('account.link_public', 'Public');
        actions.appendChild(publicLink);
      }
      const manageLink = document.createElement('a');
      manageLink.className = 'button secondary';
      manageLink.href = item.manage_url;
      manageLink.textContent = t('account.link_manage', 'Manage');
      actions.appendChild(manageLink);
      const deleteButton = document.createElement('button');
      deleteButton.className = 'button secondary';
      deleteButton.textContent = t('common.delete', 'Delete');
      deleteButton.disabled = state.isBanned;
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
          setAccountStatus(t('account.status_delete_failed', 'Delete failed.'));
          deleteButton.disabled = false;
        }
      });
      actions.appendChild(deleteButton);
      card.appendChild(img);
      card.appendChild(meta);
      card.appendChild(actions);
      uploadsGrid.appendChild(card);
    });
  };

  const updatePagination = () => {
    const totalPages = Math.max(1, Math.ceil(state.total / state.perPage));
    pageInfo.textContent = `${state.page} / ${totalPages}`;
    prevPage.disabled = state.page <= 1;
    nextPage.disabled = state.page >= totalPages;
  };

  const loadUploads = async () => {
    try {
      const data = await requestJson(`/api/my_uploads.php?page=${state.page}&per_page=${state.perPage}`);
      state.total = data.total;
      renderUploads(data.items);
      updatePagination();
    } catch (error) {
      uploadsGrid.innerHTML = `<p>${t('account.status_uploads_error', 'Uploads could not be loaded.')}</p>`;
    }
  };

  copyButton.addEventListener('click', async () => {
    if (!state.userId) {
      return;
    }
    try {
      await navigator.clipboard.writeText(state.userId);
      setAccountStatus(t('account.status_copy_success', 'Access key copied.'));
    } catch (error) {
      setAccountStatus(t('account.status_copy_failed', 'Copy failed.'));
    }
  });

  saveTtl.addEventListener('click', async () => {
    const value = ttlSelect.value;
    try {
      await requestJson('/api/user_settings.php', {
        method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ttl_seconds: value }),
    });
      setAccountStatus(t('account.status_ttl_saved', 'TTL saved.'));
    } catch (error) {
      setAccountStatus(t('account.status_ttl_failed', 'TTL could not be saved.'));
    }
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

  loadAccount().then(loadUploads);
</script>
</body>
</html>
