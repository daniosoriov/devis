<?php

/**
 * Implements theme_preprocess_user_profile().
 */
function devis_preprocess_user_profile(&$variables) {
  //$account = user_load(347);
  //dpm($account, 'account');
  //dpm(user_pass_reset_url($account) .'/login');
  
  $variables['ds_content'] = str_replace(array('Courriel'), array('E-mail'), $variables['ds_content']);
}