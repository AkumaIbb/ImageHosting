<?php
require_once __DIR__ . '/../lib/cleanup.php';
require_once __DIR__ . '/../lib/shortcodes.php';

ih_maybe_cleanup();

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
  </style>
</head>
<body>
  <main class="shell">
    <header>
      <h1>ImageHosting – Easy Image Uploads</h1>
      <p>Ziehe Bilder hier hinein, wähle sie per Klick oder füge sie direkt aus der Zwischenablage ein.</p>
    </header>

    <section class="card">
      <div class="dropzone" id="dropzone">
        <div class="dropzone__icon" aria-hidden="true"></div>
        <div>
          <h2>Copy &amp; Paste, Drag &amp; Drop</h2>
          <p class="dropzone__hint">Mehrere Bilder möglich – wir erstellen automatisch ein Album.</p>
        </div>
        <div class="spinner" aria-hidden="true"></div>
        <div class="controls">
          <label class="button" for="fileInput">Bilder auswählen</label>
          <input id="fileInput" type="file" accept="image/*" multiple>
          <input id="fileName" class="input" type="text" placeholder="Keine Dateien ausgewählt" readonly>
          <button id="uploadButton">Upload starten</button>
        </div>
      </div>
      <div class="status" id="uploadStatus">
        <strong>Bereit für den Upload.</strong>
        <small>Tippe Strg+V, um ein Bild aus der Zwischenablage einzufügen.</small>
      </div>
    </section>

    <footer>
      <p>Uploads bleiben 48 Stunden verfügbar. Teile danach einfach den neuen Link.</p>
    </footer>
  </main>

  <script src="/app.js"></script>
</body>
</html>
