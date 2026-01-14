<?php
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ImageHosting</title>
</head>
<body>
  <main>
    <h1>ImageHosting</h1>
    <p>Minimaler Startpunkt für die Web-App. Die API-Endpunkte liefern aktuell nur Platzhalter-Antworten.</p>

    <section>
      <h2>Aktionen</h2>
      <ul>
        <li><code>POST /api/upload.php</code> – Upload eines Bildes</li>
        <li><code>POST /api/album_create.php</code> – Album erstellen</li>
        <li><code>GET /api/images.php</code> – Bildliste</li>
        <li><code>POST /api/delete.php</code> – Bild löschen</li>
      </ul>
    </section>
  </main>

  <script src="/app.js"></script>
</body>
</html>
