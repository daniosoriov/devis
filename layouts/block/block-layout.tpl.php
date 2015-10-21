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
        <!--<svg class="navigation-svg" xml:space="preserve" enable-background="new 0 0 44 34" viewBox="0 0 44 34" height="34px" width="44px" y="0px" x="0px" version="1.1">
          <rect height="2px" width="22px" y="16px" x="11px"></rect>
          <rect height="2px" width="22px" y="10px" x="11px"></rect>
          <rect height="2px" width="22px" y="22px" x="11px"></rect>
        </svg> -->
      </nav>
    </div>
  </header>

  <div class="l-main-block">
    <div class="l-content-block" role="main">
      <?php print $messages; ?>
      <?php print render($page['content']); ?>
    </div>
  </div>
    
    <footer id="page-footer" class="l-footer" role="contentinfo">
        <?php print render($page['footer']); ?>
    </footer>
</div>