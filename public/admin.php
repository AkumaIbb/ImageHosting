<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/cleanup.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';

ih_maybe_cleanup();

$cookieUserId = ih_get_user_id_cookie();
if (!is_admin($cookieUserId)) {
    header('Location: /admin_login.php', true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel – ImageHosting</title>
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
  <header>
    <h1>Admin Panel</h1>
    <p>Alle Uploads verwalten und User sperren.</p>
  </header>

  <section class="card">
    <h2>Filter</h2>
    <div class="row">
      <input class="input" id="filterUserId" type="text" placeholder="User-ID filtern (optional)">
      <button class="button secondary" id="applyFilter" type="button">Filter anwenden</button>
      <button class="button secondary" id="clearFilter" type="button">Filter löschen</button>
    </div>
    <div class="status" id="adminStatus">Bereit.</div>
  </section>

  <section class="card">
    <h2>Uploads</h2>
    <div class="grid" id="uploadsGrid"></div>
    <div class="pagination">
      <button class="button secondary" id="prevPage" type="button">Zurück</button>
      <span id="pageInfo"></span>
      <button class="button secondary" id="nextPage" type="button">Weiter</button>
    </div>
  </section>
</main>

<script>
  const state = { page: 1, perPage: 24, total: 0, filterUserId: '' };
  const adminStatus = document.getElementById('adminStatus');
  const filterInput = document.getElementById('filterUserId');
  const uploadsGrid = document.getElementById('uploadsGrid');
  const prevPage = document.getElementById('prevPage');
  const nextPage = document.getElementById('nextPage');
  const pageInfo = document.getElementById('pageInfo');

  const fallbackImage = 'data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="200"><rect width="100%" height="100%" fill="#111723"/><text x="50%" y="50%" fill="#6c768f" font-family="Segoe UI, sans-serif" font-size="16" dominant-baseline="middle" text-anchor="middle">No preview</text></svg>');

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

  const updatePagination = () => {
    const totalPages = Math.max(1, Math.ceil(state.total / state.perPage));
    pageInfo.textContent = `${state.page} / ${totalPages}`;
    prevPage.disabled = state.page <= 1;
    nextPage.disabled = state.page >= totalPages;
  };

  const renderUploads = (items) => {
    uploadsGrid.innerHTML = '';
    if (!items.length) {
      uploadsGrid.innerHTML = '<p>Keine Uploads gefunden.</p>';
      return;
    }
    items.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'thumb';
      const img = document.createElement('img');
      img.src = item.preview_url || fallbackImage;
      img.alt = 'Upload Vorschau';
      const meta = document.createElement('small');
      meta.textContent = `Upload ${item.upload_id} | User ${item.user_id ?? 'anonymous'}`;
      const actions = document.createElement('div');
      actions.className = 'row';
      if (item.public_url) {
        const publicLink = document.createElement('a');
        publicLink.className = 'button secondary';
        publicLink.href = item.public_url;
        publicLink.textContent = 'Public';
        actions.appendChild(publicLink);
      }
      const manageLink = document.createElement('a');
      manageLink.className = 'button secondary';
      manageLink.href = item.manage_url;
      manageLink.textContent = 'Manage';
      actions.appendChild(manageLink);

      const deleteButton = document.createElement('button');
      deleteButton.className = 'button secondary';
      deleteButton.textContent = 'Löschen';
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
          setStatus('Löschen fehlgeschlagen.');
          deleteButton.disabled = false;
        }
      });
      actions.appendChild(deleteButton);

      if (item.user_id) {
        const banButton = document.createElement('button');
        banButton.className = 'button secondary';
        banButton.textContent = item.is_banned ? 'Entsperren' : 'Sperren';
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
            setStatus('Ban-Update fehlgeschlagen.');
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
      uploadsGrid.innerHTML = '<p>Uploads konnten nicht geladen werden.</p>';
    }
  };

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

  loadUploads();
</script>
</body>
</html>
