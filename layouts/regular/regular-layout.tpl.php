<?php

?>
<div id="page-wrapper" class="l-page">
  <header class="l-header-content">
    <div class="header-container">
      <?php print render($page['header']); ?>
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
