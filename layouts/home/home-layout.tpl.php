<?php

?>
<div id="page-wrapper" class="l-page">
  <header class="l-header-content">
    <div class="header-container">
      <div class="l-region-custom l-region--header-custom">
        <div class="panel-pane-custom pane-page-logo-custom">
          <a id="logo" title="<?php print t('3devis') ?>" rel="home" href="/">
            <img src="http://3devis.be/sites/default/files/comptable-fiscaliste-bruxelles.png" alt="<?php print t('3devis'); ?>" />
          </a>
        </div>
      </div>
      <!--<?php print render($page['header']); ?>-->
      
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
