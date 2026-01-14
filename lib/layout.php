<?php
declare(strict_types=1);

require_once __DIR__ . '/i18n.php';

function ih_render_topbar(string $lang, bool $isLoggedIn, bool $isAdmin): void
{
    ?>
    <div class="topbar">
      <a class="topbar__brand" href="/">ImageHosting</a>
      <nav class="topbar__links" aria-label="Primary">
        <a href="/"><?= htmlspecialchars(ih_t('nav.home', $lang), ENT_QUOTES, 'UTF-8') ?></a>
        <?php if ($isLoggedIn): ?>
          <a href="/account.php"><?= htmlspecialchars(ih_t('nav.uploads', $lang), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
          <a href="/admin.php"><?= htmlspecialchars(ih_t('nav.admin', $lang), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
      </nav>
      <div class="topbar__lang">
        <label for="langSelect"><?= htmlspecialchars(ih_t('nav.language', $lang), ENT_QUOTES, 'UTF-8') ?></label>
        <select id="langSelect" name="lang">
          <?php foreach (ih_supported_languages() as $code): ?>
            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $code === $lang ? 'selected' : '' ?>>
              <?= htmlspecialchars(ih_t('lang.' . $code, $lang), ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php
}
