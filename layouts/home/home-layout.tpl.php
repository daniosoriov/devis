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

  <div class="l-main-home">
    <div class="l-content-home" role="main">
      <?php print $messages; ?>
      <?php print render($page['content']); ?>
    </div>
  </div>
    
    <footer id="page-footer" class="l-footer" role="contentinfo">
        <?php print render($page['footer']); ?>
    </footer>
</div>
