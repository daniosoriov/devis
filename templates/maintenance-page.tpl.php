<?php

/**
 * @file
 * Default theme implementation to display a single Drupal page while offline.
 *
 * All the available variables are mirrored in html.tpl.php and page.tpl.php.
 * Some may be blank but they are provided for consistency.
 *
 * @see template_preprocess()
 * @see template_preprocess_maintenance_page()
 *
 * @ingroup themeable
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">

<head>
  <title><?php print $head_title; ?></title>
  <?php print $head; ?>
  <?php print $styles; ?>
  <?php print $scripts; ?>
</head>
<body class="<?php print $classes; ?>">
  <div id="page-wrapper" class="l-page">
    <header class="l-header-content">
      <div class="header-container maintenance">
        <?php if (!empty($logo)): ?>
          <a href="<?php print $base_path; ?>" title="<?php print t('Home'); ?>" rel="home" id="logo">
            <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" />
          </a>
        <?php endif; ?>
      </div>
    </header>

    <div class="l-main-block maintenance">
      <div class="l-content-block" role="main">
        <?php if (!empty($title)): ?><h1 class="title" id="page-title"><?php print $title; ?></h1><?php endif; ?>
        <?php if (!empty($messages)): print $messages; endif; ?>
        <?php print $content; ?>
      </div>
    </div>

    <footer id="page-footer" class="l-footer maintenance" role="contentinfo">
      <?php if (!empty($footer)): print $footer; endif; ?>
    </footer>
  </div>
</body>
</html>