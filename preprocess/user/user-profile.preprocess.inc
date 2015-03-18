<?php

/**
 * Implements theme_preprocess_user_profile().
 */
function devis_preprocess_user_profile(&$variables) {
  //$account = user_load(347);
  //dpm($account, 'account');
  //dpm(user_pass_reset_url($account) .'/login');
  
  $variables['user_profile']['field_prenom']['#access'] = FALSE;
  if (isset($variables['user_profile']['field_name'])) {
    $variables['user_profile']['field_name'][0]['#markup'] = 
      $variables['user_profile']['field_prenom'][0]['#markup'] .' '. $variables['user_profile']['field_name'][0]['#markup'];
  }
  
  // Substitute Courriel by E-mail.
  $variables['ds_content'] = str_replace(array('Courriel'), array('E-mail'), $variables['ds_content']);
  if (isset($variables['user_profile']['group_profile']['user_mail'])) {
    $variables['user_profile']['group_profile']['user_mail']['#title'] = 'E-mail';
    $variables['elements']['user_mail']['#title'] = 'E-mail';
    $variables['elements']['group_profile']['user_mail']['#title'] = 'E-mail';
  }

  if (isset($variables['user_profile']['field_account_activity_status'])) {
    if (!$variables['user_profile']['field_account_activity_status']['#items'][0]['value']) {
      $variables['user_profile']['field_account_activity_status'][0]['#markup'] = '<span class="inactive">'. $variables['user_profile']['field_account_activity_status'][0]['#markup'] .'</span>';
    }
  }
}