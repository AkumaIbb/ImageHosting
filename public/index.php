<?php
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ImageHosting</title>
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

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
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

    .response {
      margin-top: 14px;
      background: rgba(8, 10, 16, 0.9);
      border-radius: 12px;
      padding: 14px;
      font-family: "JetBrains Mono", "Fira Code", monospace;
      font-size: 0.9rem;
      color: #c7f0ff;
      min-height: 120px;
      overflow-x: auto;
    }

    .endpoint-list {
      list-style: none;
      display: grid;
      gap: 10px;
      font-size: 0.95rem;
    }

    .endpoint-list code {
      color: #8ad1ff;
      font-weight: 600;
    }

    footer {
      text-align: center;
      color: #97a1b7;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>
  <main class="shell">
    <header>
      <h1>ImageHosting – Easy Image Uploads</h1>
      <p>Kopieren, ziehen oder per Klick hochladen – und live mit der API kommunizieren.</p>
    </header>

    <section class="card">
      <div class="dropzone" id="dropzone">
        <div class="dropzone__icon" aria-hidden="true"></div>
        <div>
          <h2>Copy &amp; Paste oder Drag &amp; Drop</h2>
          <p class="dropzone__hint">Ziehe ein Bild hier hinein oder wähle eine Datei aus.</p>
        </div>
        <div class="controls">
          <label class="button" for="fileInput">Bild auswählen</label>
          <input id="fileInput" type="file" accept="image/*">
          <input id="fileName" class="input" type="text" placeholder="/pfad/zu/deinem/bild.png" readonly>
          <button id="uploadButton">Upload starten</button>
        </div>
      </div>
      <pre class="response" id="uploadResponse">Upload-Status erscheint hier ...</pre>
    </section>

    <section class="grid">
      <div class="card">
        <h2>Alben erstellen</h2>
        <p class="dropzone__hint">Lege ein neues Album über <code>POST /api/album_create.php</code> an.</p>
        <div class="controls">
          <input id="albumName" class="input" type="text" placeholder="Album-Name">
          <button id="albumButton" class="secondary">Album anlegen</button>
        </div>
        <pre class="response" id="albumResponse">Album-Status erscheint hier ...</pre>
      </div>

      <div class="card">
        <h2>Bilder abrufen</h2>
        <p class="dropzone__hint">Hole eine Bildliste über <code>GET /api/images.php</code>.</p>
        <div class="controls">
          <button id="listButton" class="secondary">Bildliste laden</button>
        </div>
        <pre class="response" id="listResponse">Antwort erscheint hier ...</pre>
      </div>

      <div class="card">
        <h2>Bild löschen</h2>
        <p class="dropzone__hint">Entferne ein Bild über <code>POST /api/delete.php</code>.</p>
        <div class="controls">
          <input id="deleteId" class="input" type="text" placeholder="Bild-ID oder Datei">
          <button id="deleteButton" class="secondary">Bild löschen</button>
        </div>
        <pre class="response" id="deleteResponse">Lösch-Status erscheint hier ...</pre>
      </div>
    </section>

    <section class="card">
      <h2>Endpoints</h2>
      <ul class="endpoint-list">
        <li><code>POST /api/upload.php</code> – Bild-Upload</li>
        <li><code>POST /api/album_create.php</code> – Album anlegen</li>
        <li><code>GET /api/images.php</code> – Bilder abrufen</li>
        <li><code>POST /api/delete.php</code> – Bild löschen</li>
      </ul>
    </section>

    <footer>
      <p>ImageHosting Demo-UI – bereit für den nächsten Ausbau.</p>
    </footer>
  </main>

  <script src="/app.js"></script>
</body>
</html>
