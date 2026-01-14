# ImageHosting

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
