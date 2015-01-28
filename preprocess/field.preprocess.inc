<?php

/**
 * Implements hook_preprocess_field().
 */
function devis_preprocess_field(&$variables, $hook) {
  if (
    isset($variables['element']['#items'][0]) && (
      !isset($variables['element']['#items'][0]['format']) ||
      $variables['element']['#items'][0]['format'] === 'text_plain'
    )
  ) {
    foreach ($variables['items'] as $index => $value) {
      $markup = isset($variables['items'][$index]['#markup']) ? $variables['items'][$index]['#markup'] : '';
      $variables['items'][$index]['#markup'] = nl2br($markup);
    }
  }
}