<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/cleanup.php';
require_once __DIR__ . '/../lib/uploads.php';

ih_maybe_cleanup();

$uploadId = ih_sanitize_id($_GET['id'] ?? null);
$upload = $uploadId ? ih_load_upload($uploadId) : null;
$isMissing = !$upload;
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Upload verwalten – ImageHosting</title>
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
  <header>
    <h1>Upload verwalten</h1>
    <p>Teile den Verwaltungslink nur mit Personen, die Inhalte bearbeiten dürfen.</p>
  </header>

  <?php if ($isMissing): ?>
    <section class="card">
      <h2>Upload nicht gefunden</h2>
      <p>Dieser Upload ist abgelaufen oder wurde gelöscht. Bitte starte einen neuen Upload.</p>
      <a class="button" href="/">Zur Startseite</a>
    </section>
  <?php else: ?>
    <section class="card">
      <h2><?php echo $upload['type'] === 'album' ? 'Album-Upload' : 'Einzelbild-Upload'; ?></h2>
      <div class="actions">
        <a class="button secondary" href="<?php echo '/v.php?id=' . $uploadId; ?>">Öffentliche Ansicht</a>
        <button class="button" id="addButton" type="button">Weitere Bilder hinzufügen</button>
      </div>
      <div class="dropzone" id="dropzone">
        <p>Ziehe Bilder hier hinein oder füge sie per Strg+V hinzu.</p>
        <label class="button secondary" for="fileInput">Bilder auswählen</label>
        <input id="fileInput" type="file" accept="image/*" multiple>
        <input id="fileName" type="text" class="input" readonly placeholder="Keine Dateien ausgewählt">
      </div>
      <div class="status" id="status">Bereit.</div>
    </section>

    <section class="card">
      <h2>Inhalte</h2>
      <div class="grid">
        <?php foreach ($upload['files'] as $file): ?>
          <div class="thumb">
            <img src="<?php echo ih_public_file_url($uploadId, $file['filename']); ?>" alt="Upload Bild">
            <small><?php echo htmlspecialchars($file['original'], ENT_QUOTES); ?></small>
            <button class="button secondary delete-button" data-file-id="<?php echo $file['id']; ?>" type="button">Bild löschen</button>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <footer>Uploads bleiben 48 Stunden verfügbar.</footer>
</main>

<?php if (!$isMissing): ?>
<script>
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
      fileName.value = 'Keine Dateien ausgewählt';
      return;
    }
    fileName.value = `${state.files.length} Datei(en) ausgewählt`;
  };

  const addFiles = (files) => {
    const images = Array.from(files).filter((file) => file.type.startsWith('image/'));
    if (images.length === 0) {
      renderStatus('Bitte nur Bilddateien hinzufügen.');
      return;
    }
    state.files = images;
    updateFileDisplay();
    renderStatus(`${images.length} Bild(er) bereit zum Hochladen.`);
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
      renderStatus('Bitte zuerst Bilder auswählen.');
      return;
    }
    const formData = new FormData();
    state.files.forEach((file) => {
      formData.append('files[]', file);
    });
    formData.append('upload_id', uploadId);

    state.isUploading = true;
    dropzone.classList.add('is-loading');
    renderStatus('Upload läuft ...');

    try {
      const response = await fetch('/api/upload.php', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Upload fehlgeschlagen.');
      }
      renderStatus('Upload abgeschlossen. Seite wird aktualisiert ...');
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
      renderStatus('Löschen läuft ...');
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
        renderStatus(error.message);
      }
    });
  });
</script>
<?php endif; ?>
</body>
</html>
