<?php

/**
 * Implements theme_preprocess_views_view().
 */
function devis_preprocess_views_view(&$variables) {
  switch ($variables['name']) {
    case 'commerce_user_orders':
      $variables['empty'] = t('You have not placed any orders with us yet.');
      break;
    
    case 'commerce_addressbook_defaults':
      //$variables['title'] = '<h3 class="title">'. t('Default address') .'</h3>';
      break;
    
    case 'commerce_addressbook':
      //$variables['title'] = '<h3 class="title">'. t('Other addresses') .'</h3>';
      break;
  }
}