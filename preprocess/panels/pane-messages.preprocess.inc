<?php

/**
 * Implements theme_preprocess_pane_messages().
 */
function devis_preprocess_pane_messages(&$variables) {
  // If the user is viewing his cards, unset the action links on the pane_messages.
  $path = current_path();
  $path_alias = drupal_lookup_path('alias', $path);
  if ($path == 'user/'. $variables['user']->uid .'/cards') {
    $variables['action_links'] = array();
  }
  // If the user is viewing his addresses, unset the action links on the pane_messages.
  if ($path == 'user/'. $variables['user']->uid .'/addressbook/billing') {
    $variables['action_links'] = array();
  }
}