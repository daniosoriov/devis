<?php

/**
 * Implements theme_preprocess_user_login().
 */
function devis_preprocess_user_login(&$variables) {
  $variables['title'] = t('User login');
  $variables['password_url'] = url('user/password');
  $variables['password_label'] = t('Forgot password?');
  $variables['register_url'] = url('eform/submit/devenir');
  $variables['register_label'] = t('Become provider');
}