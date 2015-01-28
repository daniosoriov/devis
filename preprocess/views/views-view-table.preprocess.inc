<?php

/**
 * Implements theme_preprocess_views_view_table().
 */
function devis_preprocess_views_view_table(&$variables) {
  switch ($variables['view']->name) {
    case 'commerce_user_orders':
      // Update header names.
      foreach ($variables['header'] as $key => $val) {
        $variables['header'][$key] = t($val);
      }
      break;
    
    case 'commerce_line_item_table':
      // Update header names.
      foreach ($variables['header'] as $key => $val) {
        if ($val == 'Titre') {
          $val = 'Product';
        }
        $variables['header'][$key] = t($val);
      }
      break;
  }
}