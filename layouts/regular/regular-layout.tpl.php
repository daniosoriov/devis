<?php

?>
<div id="page-wrapper" class="l-page">
  <header class="l-header-content">
    <div class="header-container">
      <div class="l-region-custom l-region--header-custom">
        <div class="panel-pane-custom pane-page-logo-custom">
          <a id="logo" title="<?php print t('3devis') ?>" rel="home" href="/">
            <img src="https://3devis.be/sites/default/files/comptable-fiscaliste-bruxelles.png" alt="<?php print t('3devis'); ?>" />
          </a>
        </div>
      </div>
      <!--<?php print render($page['header']); ?>-->
      
      <nav class="l-navigation-content">
        <?php print render($page['navigation']); ?>
      </nav>
    </div>
  </header>

  <div class="l-main">
    <div class="l-content" role="main">
      <?php print render($page['highlighted']); ?>
      <?php print $breadcrumb; ?>
      <a id="main-content"></a>
      <?php print render($title_prefix); ?>
      <?php if ($title): ?>
        <h1><?php print $title; ?></h1>
      <?php endif; ?>
      <?php print render($title_suffix); ?>
      <?php print $messages; ?>
      <?php print render($tabs); ?>
      <?php print render($page['help']); ?>
      <?php if ($action_links): ?>
        <ul class="action-links"><?php print render($action_links); ?></ul>
      <?php endif; ?>
      <?php print render($page['content']); ?>
      <?php print $feed_icons; ?>
    </div>
  </div>
    
    <footer id="page-footer" class="l-footer" role="contentinfo">
        <?php print render($page['footer']); ?>
    </footer>
</div>
