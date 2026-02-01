<?php
declare(strict_types=1);

const IH_LANG_COOKIE = 'ih_lang';

function ih_supported_languages(): array
{
    return ['de', 'en'];
}

function ih_detect_browser_language(?string $header): string
{
    if (!$header) {
        return 'en';
    }
    $header = strtolower($header);
    foreach (ih_supported_languages() as $language) {
        if (preg_match('/\b' . preg_quote($language, '/') . '\b/', $header)) {
            return $language;
        }
    }
    return 'en';
}

function ih_set_language_cookie(string $language): void
{
    setcookie(IH_LANG_COOKIE, $language, [
        'expires' => time() + 31536000,
        'path' => '/',
        'samesite' => 'Lax',
    ]);
}

function ih_get_language(): string
{
    $supported = ih_supported_languages();
    $requested = $_GET['lang'] ?? null;
    if (is_string($requested)) {
        $requested = strtolower(trim($requested));
        if (in_array($requested, $supported, true)) {
            ih_set_language_cookie($requested);
            return $requested;
        }
    }

    $cookieLang = $_COOKIE[IH_LANG_COOKIE] ?? null;
    if (is_string($cookieLang)) {
        $cookieLang = strtolower(trim($cookieLang));
        if (in_array($cookieLang, $supported, true)) {
            return $cookieLang;
        }
    }

    return ih_detect_browser_language($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null);
}

function ih_translations(): array
{
    return [
        'de' => [
            'nav.home' => 'Home',
            'nav.uploads' => 'Meine Uploads',
            'nav.admin' => 'Admin Panel',
            'nav.language' => 'Sprache',
            'lang.de' => 'Deutsch',
            'lang.en' => 'English',
            'footer.disclaimer' => 'Hobbyprojekt, nicht in Verbindung mit https://f-list.net',
            'footer.rules' => 'Regeln: Adult Content ist erlaubt, sofern klar 18+ und legal (gilt auch für animierte Inhalte).',
            'footer.as_is' => 'Der Dienst wird „wie besehen“ („as is“) ohne Gewährleistung, Verfügbarkeitsgarantie oder sonstige Zusicherungen bereitgestellt.',
            'common.public' => 'Öffentlich',
            'common.manage' => 'Verwalten',
            'common.delete' => 'Löschen',
            'common.anonymous' => 'anonym',
            'index.hero_title' => 'ImageHosting – einfache Bild-Uploads',
            'index.hero_subtitle' => 'Ziehe Bilder hier hinein, wähle sie per Klick oder füge sie direkt aus der Zwischenablage ein.',
            'index.drop_title' => 'Copy & Paste, Drag & Drop',
            'index.drop_hint' => 'Mehrere Bilder möglich – wir erstellen automatisch ein Album.',
            'index.select_images' => 'Bilder auswählen',
            'index.upload_start' => 'Upload starten',
            'index.status_ready_title' => 'Bereit für den Upload.',
            'index.status_ready_detail' => 'Tippe Strg+V, um ein Bild aus der Zwischenablage einzufügen.',
            'index.account_title' => 'Account (anonym)',
            'index.account_desc' => 'Optionaler Zugangsschlüssel, um Uploads dauerhaft zu verwalten. Der Schlüssel ist nicht wiederherstellbar.',
            'index.account_create' => 'Account anlegen (anonym)',
            'index.account_uploads' => 'Meine Uploads',
            'index.account_admin' => 'Admin Panel',
            'index.account_status_title' => 'Noch kein Account hinterlegt.',
            'index.account_status_detail' => 'Du kannst einen anonymen Zugangsschlüssel erstellen und sicher speichern.',
            'app.files_none' => 'Keine Dateien ausgewählt',
            'app.files_many' => '{{count}} Dateien ausgewählt',
            'app.images_ready_title' => '{{count}} Bild(er) bereit',
            'app.images_ready_detail' => 'Klicke auf Upload starten, um fortzufahren.',
            'app.invalid_response' => 'Ungültige Serverantwort',
            'app.upload_failed' => 'Upload fehlgeschlagen',
            'app.select_image_first' => 'Bitte zuerst ein Bild auswählen.',
            'app.select_image_detail' => 'Ziehe Dateien hier hinein oder nutze Strg+V.',
            'app.upload_in_progress_title' => 'Upload läuft ...',
            'app.upload_in_progress_detail' => 'Bitte kurze Geduld.',
            'app.upload_done_title' => 'Upload abgeschlossen!',
            'app.upload_done_detail' => 'Weiterleitung zur Verwaltung ...',
            'app.upload_failed_title' => 'Upload fehlgeschlagen',
            'app.upload_failed_detail' => 'Bitte erneut versuchen.',
            'app.account_active_title' => 'Account aktiv.',
            'app.account_active_detail' => 'Dein Zugangsschlüssel ist im Cookie hinterlegt. Bitte sicher aufbewahren.',
            'app.account_create_title' => 'Account wird erstellt ...',
            'app.account_create_detail' => 'Bitte kurz warten.',
            'app.copy_key_label' => 'Zugangsschlüssel kopieren',
            'app.copy_key_note' => 'Dieser Schlüssel ist nicht wiederherstellbar. Bitte sicher speichern.',
            'app.account_created_title' => 'Account erstellt.',
            'app.account_created_detail' => 'Zugangsschlüssel einmalig angezeigt:',
            'app.account_create_failed_title' => 'Account konnte nicht erstellt werden.',
            'app.account_create_failed_detail' => 'Bitte erneut versuchen.',
            'account.title' => 'Mein Account',
            'account.subtitle' => 'Verwalte deinen Zugangsschlüssel und deine Uploads.',
            'account.key_title' => 'Zugangsschlüssel',
            'account.key_placeholder' => 'Kein Account gefunden',
            'account.copy_key' => 'Schlüssel kopieren',
            'account.key_desc' => 'Der Zugangsschlüssel ist die einzige Möglichkeit, deine Uploads zu verwalten. Er ist nicht wiederherstellbar.',
            'account.ttl_label' => 'Standard-TTL',
            'account.ttl_save' => 'TTL speichern',
            'account.status_loading' => 'Lade Accountdaten ...',
            'account.uploads_title' => 'Deine Uploads',
            'account.pagination_prev' => 'Zurück',
            'account.pagination_next' => 'Weiter',
            'account.status_no_account' => 'Kein Account gefunden. Bitte auf der Startseite erstellen.',
            'account.status_active' => 'Account aktiv.',
            'account.status_banned' => 'Account ist gesperrt.',
            'account.status_load_error' => 'Accountdaten konnten nicht geladen werden.',
            'account.status_delete_failed' => 'Löschen fehlgeschlagen.',
            'account.status_uploads_error' => 'Uploads konnten nicht geladen werden.',
            'account.status_copy_success' => 'Zugangsschlüssel kopiert.',
            'account.status_copy_failed' => 'Kopieren fehlgeschlagen.',
            'account.status_ttl_saved' => 'TTL gespeichert.',
            'account.status_ttl_failed' => 'TTL konnte nicht gespeichert werden.',
            'account.no_uploads' => 'Keine Uploads vorhanden.',
            'account.upload_preview_alt' => 'Upload Vorschau',
            'account.upload_label' => 'Upload {{id}}',
            'account.link_public' => 'Öffentlich',
            'account.link_manage' => 'Verwalten',
            'account.no_preview' => 'Keine Vorschau',
            'account.ttl_unlimited' => 'Unbegrenzt',
            'admin.title' => 'Admin Panel',
            'admin.subtitle' => 'Alle Uploads verwalten und User sperren.',
            'admin.filter_title' => 'Filter',
            'admin.filter_placeholder' => 'User-ID filtern (optional)',
            'admin.filter_apply' => 'Filter anwenden',
            'admin.filter_clear' => 'Filter löschen',
            'admin.status_ready' => 'Bereit.',
            'admin.default_ttl_title' => 'Standard-Aufbewahrung',
            'admin.default_ttl_label' => 'Aufbewahrung in Stunden (0 = unbegrenzt)',
            'admin.default_ttl_save' => 'Standard speichern',
            'admin.default_ttl_status_loading' => 'Standard wird geladen ...',
            'admin.default_ttl_status_ready' => 'Standard geladen.',
            'admin.default_ttl_saved' => 'Standard gespeichert.',
            'admin.default_ttl_save_failed' => 'Standard konnte nicht gespeichert werden.',
            'admin.default_ttl_status_failed' => 'Standard konnte nicht geladen werden.',
            'admin.default_ttl_invalid' => 'Bitte eine gültige Zahl eingeben.',
            'admin.uploads_title' => 'Uploads',
            'admin.pagination_prev' => 'Zurück',
            'admin.pagination_next' => 'Weiter',
            'admin.no_uploads' => 'Keine Uploads gefunden.',
            'admin.upload_preview_alt' => 'Upload Vorschau',
            'admin.upload_label' => 'Upload {{id}} | User {{user}}',
            'admin.delete_failed' => 'Löschen fehlgeschlagen.',
            'admin.ban' => 'Sperren',
            'admin.unban' => 'Entsperren',
            'admin.ban_failed' => 'Ban-Update fehlgeschlagen.',
            'admin.uploads_error' => 'Uploads konnten nicht geladen werden.',
            'admin_login.title' => 'Admin Login',
            'admin_login.subtitle' => 'Bitte Admin-Token eingeben, um den Adminbereich zu öffnen.',
            'admin_login.placeholder' => 'Admin Token',
            'admin_login.button' => 'Anmelden',
            'admin_login.error' => 'Login fehlgeschlagen.',
            'admin_login.unauthorized' => 'Nicht autorisiert.',
            'gallery.title' => 'Galerie',
            'gallery.subtitle' => 'Öffentliche Ansicht dieses Uploads.',
            'gallery.missing_title' => 'Nicht verfügbar',
            'gallery.missing_detail' => 'Dieser Upload ist abgelaufen oder wurde gelöscht.',
            'gallery.direct_link' => 'Direktlink öffnen',
            'gallery.image_alt' => 'Bild',
            'manage.title' => 'Upload verwalten',
            'manage.subtitle' => 'Teile den Verwaltungslink nur mit Personen, die Inhalte bearbeiten dürfen.',
            'manage.missing_title' => 'Upload nicht gefunden',
            'manage.missing_detail' => 'Dieser Upload ist abgelaufen oder wurde gelöscht. Bitte starte einen neuen Upload.',
            'manage.back_home' => 'Zur Startseite',
            'manage.upload_album' => 'Album-Upload',
            'manage.upload_single' => 'Einzelbild-Upload',
            'manage.public_view' => 'Öffentliche Ansicht',
            'manage.public_unavailable' => 'Öffentliche Ansicht nicht verfügbar',
            'manage.add_images' => 'Weitere Bilder hinzufügen',
            'manage.drop_hint' => 'Ziehe Bilder hier hinein oder füge sie per Strg+V hinzu.',
            'manage.select_images' => 'Bilder auswählen',
            'manage.file_placeholder' => 'Keine Dateien ausgewählt',
            'manage.not_authorized' => 'Dieser Upload gehört zu einem Account. Aktionen sind nur mit passendem Zugangsschlüssel möglich.',
            'manage.status_ready' => 'Bereit.',
            'manage.contents_title' => 'Inhalte',
            'manage.image_alt' => 'Upload Bild',
            'manage.delete_image' => 'Bild löschen',
            'manage.expires_default' => 'Uploads bleiben 48 Stunden verfügbar.',
            'manage.expires_unlimited' => 'Uploads bleiben unbegrenzt verfügbar.',
            'manage.expires_at' => 'Ablauf: {{date}}',
            'manage.files_none' => 'Keine Dateien ausgewählt',
            'manage.files_selected' => '{{count}} Datei(en) ausgewählt',
            'manage.only_images' => 'Bitte nur Bilddateien hinzufügen.',
            'manage.images_ready' => '{{count}} Bild(er) bereit zum Hochladen.',
            'manage.select_images_first' => 'Bitte zuerst Bilder auswählen.',
            'manage.upload_running' => 'Upload läuft ...',
            'manage.upload_done' => 'Upload abgeschlossen. Seite wird aktualisiert ...',
            'manage.delete_running' => 'Löschen läuft ...',
            'manage.delete_failed' => 'Löschen fehlgeschlagen.',
        ],
        'en' => [
            'nav.home' => 'Home',
            'nav.uploads' => 'My uploads',
            'nav.admin' => 'Admin panel',
            'nav.language' => 'Language',
            'lang.de' => 'German',
            'lang.en' => 'English',
            'footer.disclaimer' => 'Hobby project, not affiliated with https://f-list.net',
            'footer.rules' => 'Rules: Adult content is allowed as long as it is clearly 18+ and legal (this also applies to animated content).',
            'footer.as_is' => 'The service is provided “as is” without warranty, availability guarantees, or other assurances.',
            'common.public' => 'Public',
            'common.manage' => 'Manage',
            'common.delete' => 'Delete',
            'common.anonymous' => 'anonymous',
            'index.hero_title' => 'ImageHosting – easy image uploads',
            'index.hero_subtitle' => 'Drag images here, pick them with a click, or paste directly from the clipboard.',
            'index.drop_title' => 'Copy & Paste, Drag & Drop',
            'index.drop_hint' => 'Multiple images are supported – we automatically create an album.',
            'index.select_images' => 'Choose images',
            'index.upload_start' => 'Start upload',
            'index.status_ready_title' => 'Ready for upload.',
            'index.status_ready_detail' => 'Press Ctrl+V to paste an image from the clipboard.',
            'index.account_title' => 'Account (anonymous)',
            'index.account_desc' => 'Optional access key to manage uploads permanently. The key cannot be recovered.',
            'index.account_create' => 'Create account (anonymous)',
            'index.account_uploads' => 'My uploads',
            'index.account_admin' => 'Admin panel',
            'index.account_status_title' => 'No account stored yet.',
            'index.account_status_detail' => 'You can create an anonymous access key and store it safely.',
            'app.files_none' => 'No files selected',
            'app.files_many' => '{{count}} files selected',
            'app.images_ready_title' => '{{count}} image(s) ready',
            'app.images_ready_detail' => 'Click start upload to continue.',
            'app.invalid_response' => 'Invalid server response',
            'app.upload_failed' => 'Upload failed',
            'app.select_image_first' => 'Please select an image first.',
            'app.select_image_detail' => 'Drag files here or use Ctrl+V.',
            'app.upload_in_progress_title' => 'Upload in progress ...',
            'app.upload_in_progress_detail' => 'Please wait a moment.',
            'app.upload_done_title' => 'Upload complete!',
            'app.upload_done_detail' => 'Redirecting to management ...',
            'app.upload_failed_title' => 'Upload failed',
            'app.upload_failed_detail' => 'Please try again.',
            'app.account_active_title' => 'Account active.',
            'app.account_active_detail' => 'Your access key is stored in the cookie. Please keep it safe.',
            'app.account_create_title' => 'Creating account ...',
            'app.account_create_detail' => 'Please wait.',
            'app.copy_key_label' => 'Copy access key',
            'app.copy_key_note' => 'This key cannot be recovered. Please store it safely.',
            'app.account_created_title' => 'Account created.',
            'app.account_created_detail' => 'Access key shown once:',
            'app.account_create_failed_title' => 'Account could not be created.',
            'app.account_create_failed_detail' => 'Please try again.',
            'account.title' => 'My account',
            'account.subtitle' => 'Manage your access key and uploads.',
            'account.key_title' => 'Access key',
            'account.key_placeholder' => 'No account found',
            'account.copy_key' => 'Copy key',
            'account.key_desc' => 'The access key is the only way to manage your uploads. It cannot be recovered.',
            'account.ttl_label' => 'Default TTL',
            'account.ttl_save' => 'Save TTL',
            'account.status_loading' => 'Loading account data ...',
            'account.uploads_title' => 'Your uploads',
            'account.pagination_prev' => 'Previous',
            'account.pagination_next' => 'Next',
            'account.status_no_account' => 'No account found. Please create one on the home page.',
            'account.status_active' => 'Account active.',
            'account.status_banned' => 'Account is banned.',
            'account.status_load_error' => 'Account data could not be loaded.',
            'account.status_delete_failed' => 'Delete failed.',
            'account.status_uploads_error' => 'Uploads could not be loaded.',
            'account.status_copy_success' => 'Access key copied.',
            'account.status_copy_failed' => 'Copy failed.',
            'account.status_ttl_saved' => 'TTL saved.',
            'account.status_ttl_failed' => 'TTL could not be saved.',
            'account.no_uploads' => 'No uploads available.',
            'account.upload_preview_alt' => 'Upload preview',
            'account.upload_label' => 'Upload {{id}}',
            'account.link_public' => 'Public',
            'account.link_manage' => 'Manage',
            'account.no_preview' => 'No preview',
            'account.ttl_unlimited' => 'Unlimited',
            'admin.title' => 'Admin panel',
            'admin.subtitle' => 'Manage all uploads and ban users.',
            'admin.filter_title' => 'Filter',
            'admin.filter_placeholder' => 'Filter by user ID (optional)',
            'admin.filter_apply' => 'Apply filter',
            'admin.filter_clear' => 'Clear filter',
            'admin.status_ready' => 'Ready.',
            'admin.default_ttl_title' => 'Default retention',
            'admin.default_ttl_label' => 'Retention in hours (0 = unlimited)',
            'admin.default_ttl_save' => 'Save default',
            'admin.default_ttl_status_loading' => 'Loading default TTL...',
            'admin.default_ttl_status_ready' => 'Default TTL loaded.',
            'admin.default_ttl_saved' => 'Default TTL saved.',
            'admin.default_ttl_save_failed' => 'Default TTL could not be saved.',
            'admin.default_ttl_status_failed' => 'Default TTL could not be loaded.',
            'admin.default_ttl_invalid' => 'Please enter a valid number.',
            'admin.uploads_title' => 'Uploads',
            'admin.pagination_prev' => 'Previous',
            'admin.pagination_next' => 'Next',
            'admin.no_uploads' => 'No uploads found.',
            'admin.upload_preview_alt' => 'Upload preview',
            'admin.upload_label' => 'Upload {{id}} | User {{user}}',
            'admin.delete_failed' => 'Delete failed.',
            'admin.ban' => 'Ban',
            'admin.unban' => 'Unban',
            'admin.ban_failed' => 'Ban update failed.',
            'admin.uploads_error' => 'Uploads could not be loaded.',
            'admin_login.title' => 'Admin login',
            'admin_login.subtitle' => 'Enter the admin token to access the admin area.',
            'admin_login.placeholder' => 'Admin token',
            'admin_login.button' => 'Sign in',
            'admin_login.error' => 'Login failed.',
            'admin_login.unauthorized' => 'Not authorized.',
            'gallery.title' => 'Gallery',
            'gallery.subtitle' => 'Public view of this upload.',
            'gallery.missing_title' => 'Unavailable',
            'gallery.missing_detail' => 'This upload has expired or was deleted.',
            'gallery.direct_link' => 'Open direct link',
            'gallery.image_alt' => 'Image',
            'manage.title' => 'Manage upload',
            'manage.subtitle' => 'Share the management link only with people allowed to edit content.',
            'manage.missing_title' => 'Upload not found',
            'manage.missing_detail' => 'This upload has expired or was deleted. Please start a new upload.',
            'manage.back_home' => 'Back to home',
            'manage.upload_album' => 'Album upload',
            'manage.upload_single' => 'Single image upload',
            'manage.public_view' => 'Public view',
            'manage.public_unavailable' => 'Public view not available',
            'manage.add_images' => 'Add more images',
            'manage.drop_hint' => 'Drag images here or paste them with Ctrl+V.',
            'manage.select_images' => 'Choose images',
            'manage.file_placeholder' => 'No files selected',
            'manage.not_authorized' => 'This upload belongs to an account. Actions require the matching access key.',
            'manage.status_ready' => 'Ready.',
            'manage.contents_title' => 'Contents',
            'manage.image_alt' => 'Upload image',
            'manage.delete_image' => 'Delete image',
            'manage.expires_default' => 'Uploads are available for 48 hours.',
            'manage.expires_unlimited' => 'Uploads are available indefinitely.',
            'manage.expires_at' => 'Expires: {{date}}',
            'manage.files_none' => 'No files selected',
            'manage.files_selected' => '{{count}} file(s) selected',
            'manage.only_images' => 'Please add image files only.',
            'manage.images_ready' => '{{count}} image(s) ready to upload.',
            'manage.select_images_first' => 'Please select images first.',
            'manage.upload_running' => 'Upload in progress ...',
            'manage.upload_done' => 'Upload complete. Reloading ...',
            'manage.delete_running' => 'Deleting ...',
            'manage.delete_failed' => 'Delete failed.',
        ],
    ];
}

function ih_t(string $key, string $lang, ?string $fallback = null): string
{
    $translations = ih_translations();
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    if (isset($translations['en'][$key])) {
        return $translations['en'][$key];
    }
    return $fallback ?? $key;
}

function ih_i18n_payload(string $lang): array
{
    $translations = ih_translations();
    return $translations[$lang] ?? $translations['en'];
}
