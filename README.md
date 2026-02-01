# ImageHosting

## Erstinstallation

1. **Beispiel-Konfigurationen kopieren** (im Verzeichnis `config/`):
   - `config/secret.sample.php` nach `config/secret.php` kopieren.
   - `config/admin_ids_example.txt` nach `config/admin_ids.txt` kopieren (oder die vorhandene Datei überschreiben).

   Beispiel:

   ```bash
   cp config/secret.sample.php config/secret.php
   cp config/admin_ids_example.txt config/admin_ids.txt
   ```

2. **Secrets setzen** in `config/secret.php`:
   - `admin_hmac_secret`: Langer, zufälliger String (32+ Zeichen).
   - `admin_login_token`: Token für das Admin-Login-Formular.

3. **Admin-IDs pflegen** in `config/admin_ids.txt`:
   - Eine Benutzer-ID (`ih_uid`) pro Zeile.
   - Kommentare sind mit `#` möglich.

> Hinweis: `config/admin_ids.txt` und `config/secret.php` sind sensibel und sollten nur vom Betreiber lesbar sein.

## Admin-Panel

1. **Login aufrufen:** Öffne `/admin_login.php`.
2. **Admin-Token eingeben:** Verwende das `admin_login_token` aus `config/secret.php`.
3. **Weiterleitung:** Nach erfolgreichem Login wirst du nach `/admin.php` (Admin-Panel) weitergeleitet.

Das Admin-Panel ist nur zugänglich, wenn **beides** stimmt:
1. Die anonyme User-ID (`ih_uid`) steht in `config/admin_ids.txt`.
2. Das gültige `ih_admin`-Cookie wurde über `/admin_login.php` gesetzt.

## Security hardening notes

### Storage execution hardening

Apache users should keep the bundled `public/storage/.htaccess` in place to disable PHP execution in the storage directory.

For nginx, add a deny rule to the storage location in your vhost (example):

```nginx
location ~* ^/storage/.*\.(php|phtml|phar|php[0-9])$ {
  deny all;
  return 403;
}
```

### Admin authentication setup

Create `config/secret.php` based on `config/secret.sample.php` and set strong values for `admin_hmac_secret` and `admin_login_token`. Keep `config/admin_ids.txt` populated with admin user IDs (one per line).

Admins must authenticate via `/admin_login.php` using the Admin Token to mint an `ih_admin` cookie. Admin access is granted only when both `ih_uid` and `ih_admin` are valid.

## Self-tests / curl examples

Blocked MIME (SVG should fail):

```bash
curl -s -o /dev/null -w "%{http_code}\n" \
  -F "file=@tests/fixtures/bad.svg" \
  http://localhost:8000/api/upload.php
```

Too many files (should return 413 + `too_many_files`):

```bash
curl -s -X POST \
  -F "file[]=@tests/fixtures/a.jpg" \
  -F "file[]=@tests/fixtures/b.jpg" \
  -F "file[]=@tests/fixtures/c.jpg" \
  -F "file[]=@tests/fixtures/d.jpg" \
  -F "file[]=@tests/fixtures/e.jpg" \
  -F "file[]=@tests/fixtures/f.jpg" \
  -F "file[]=@tests/fixtures/g.jpg" \
  -F "file[]=@tests/fixtures/h.jpg" \
  -F "file[]=@tests/fixtures/i.jpg" \
  -F "file[]=@tests/fixtures/j.jpg" \
  -F "file[]=@tests/fixtures/k.jpg" \
  -F "file[]=@tests/fixtures/l.jpg" \
  -F "file[]=@tests/fixtures/m.jpg" \
  -F "file[]=@tests/fixtures/n.jpg" \
  -F "file[]=@tests/fixtures/o.jpg" \
  -F "file[]=@tests/fixtures/p.jpg" \
  -F "file[]=@tests/fixtures/q.jpg" \
  -F "file[]=@tests/fixtures/r.jpg" \
  -F "file[]=@tests/fixtures/s.jpg" \
  -F "file[]=@tests/fixtures/t.jpg" \
  -F "file[]=@tests/fixtures/u.jpg" \
  http://localhost:8000/api/upload.php
```

Oversized file (should return 413 + `file_too_large`):

```bash
curl -s -X POST \
  -F "file=@tests/fixtures/oversize.jpg" \
  http://localhost:8000/api/upload.php
```

## Security & Betrieb

### Ordnerstruktur & Berechtigungen

Die Anwendung benötigt Schreibrechte für Upload-Metadaten, Logs, Rate-Limits und ausgelieferte Dateien:

- `data/uploads/` (Upload-Metadaten)
- `public/storage/` (ausgelieferte Bilddateien)
- `storage/logs/` (App-Logs)
- `storage/ratelimit/` (Rate-Limit-Zustand)

Empfohlene Rechte (Beispiel, an Webserver-User anpassen):

```bash
chown -R www-data:www-data data public/storage storage
chmod 750 data public/storage storage
chmod 770 storage/logs storage/ratelimit
```

### Webserver-Härtung

**Apache**: PHP-Ausführung im Storage deaktivieren (mitgeliefertes Beispiel in `public/storage/.htaccess`):

```apache
php_flag engine off
RemoveHandler .php .phtml .phar .php3 .php4 .php5 .php7 .php8
SetHandler None
Options -ExecCGI

<FilesMatch "\.(php|phtml|phar|php[0-9])$">
    Require all denied
</FilesMatch>
```

**Nginx**: PHP-Ausführung im Storage explizit blockieren:

```nginx
location ~* ^/storage/.*\.(php|phtml|phar|php[0-9])$ {
  deny all;
  return 403;
}
```

**Wichtig:** `public/storage/` darf niemals PHP ausführen.

### Security Headers

Setze zusätzlich `X-Content-Type-Options: nosniff`, um MIME-Sniffing bei Fehlkonfigurationen zu verhindern.

**Apache (global oder vHost)**:

```apache
Header always set X-Content-Type-Options "nosniff"
```

Optional nur für `/storage`:

```apache
<Location "/storage/">
    Header always set X-Content-Type-Options "nosniff"
</Location>
```

**Nginx**:

```nginx
add_header X-Content-Type-Options "nosniff" always;
```

Optional nur für `/storage`:

```nginx
location /storage/ {
  add_header X-Content-Type-Options "nosniff" always;
}
```

### PHP-Einstellungen (empfohlen)

- `upload_max_filesize = 10M` (passt zu `IH_MAX_BYTES_PER_FILE`)
- `post_max_size = 50M` (passt zu `IH_MAX_BYTES_TOTAL`)
- `max_file_uploads = 20` (passt zu `IH_MAX_FILES_PER_REQUEST`)
- `memory_limit = 128M` (ausreichend für MIME-Erkennung ohne unnötige Überbelegung)
- `display_errors = Off` (Produktion, keine internen Details)

### Admin-Setup

- Datei mit Admin-IDs: `config/admin_ids.txt` (eine ID pro Zeile, Kommentare mit `#` erlaubt)
- Admin-ID hinzufügen: eigene `ih_uid`-ID in `config/admin_ids.txt` eintragen
- Admin-Secret konfigurieren: `config/secret.php` basierend auf `config/secret.sample.php`
  - `admin_hmac_secret` (HMAC-Key für `ih_admin`-Cookie)
  - `admin_login_token` (Login-Token für `/admin_login.php`)

**Hinweis:** Admin-IDs sind sensibel, Datei nur für den Betreiber lesbar halten.

### Sicherheitsmodell (kurz)

- Die anonyme User-ID (`ih_uid`) ist der Zugriffsschlüssel (kein Passwort, keine Recovery).
- Capability-Links (`/v.php?id=...`, `/u.php?id=...`) erlauben Zugriff unabhängig vom User.
- Uploads mit gebundenem `user_id` sind nur für diesen User oder Admin sichtbar/löschbar.
- TTL wird serverseitig enforced; abgelaufene Uploads sind nicht mehr abrufbar.

### Known Limitations / Non-Goals

- Keine IP-Sperren oder IP-Blocklisten.
- Kein Content-Scanning (z. B. SVG- oder Script-Analyse außerhalb der Allowlist).
- Kein Malware-/AV-Scanning.
- Keine Abuse-Automation oder Moderations-Workflow.

### Minimaler Security-Check vor Go-Live

1. **Test:** SVG-Upload wird abgelehnt (`415/400` je nach Client) und nicht gespeichert.
2. **Test:** >10MB Datei liefert `413 file_too_large`.
3. **Test:** >20 Dateien liefern `413 too_many_files`.
4. **Test:** Upload-Rate-Limit greift (HTTP `429`).
5. **Test:** `/api/admin_uploads.php` ohne Admin-Cookie liefert `403`.
6. **Test:** Fremdes Upload-Delete liefert `403`.
7. **Test:** Direkter Zugriff auf `/storage/*.php` liefert `403`.
