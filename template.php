<?php

/**
 * @file
 * Template overrides as well as (pre-)process and alter hooks for the
 * devis theme.
 */
function devis_theme() {
  $items = array();
  $items['user_login'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'devis') . '/templates',
    'template' => 'user-login',
    'preprocess functions' => array(
       'devis_preprocess_user_login'
    ),
  );
  /*$items['user_register_form'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'devis') . '/templates',
    'template' => 'user-register-form',
    'preprocess functions' => array(
      'yourtheme_preprocess_user_register_form'
    ),
  );*/
  $items['user_pass'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'devis') . '/templates',
    'template' => 'user-pass',
    'preprocess functions' => array(
      'devis_preprocess_user_pass'
    ),
  );
  /*$items['user_profile'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'devis') . '/templates/user',
    'template' => 'user-profile',
    'preprocess functions' => array(
      'devis_preprocess_user_profile'
    ),
  );*/
  return $items;
}

function devis_preprocess_user_login(&$vars) {
    $vars['title'] = t('User login');
    $vars['password_label'] = t('Forgot password?');
    $vars['register_label'] = t('Become provider');
}

function devis_preprocess_user_pass(&$vars) {
    $vars['title'] = t('Forgot password?');
    $vars['description'] = t('Type in your e-mail address and we will send you an e-mail with instructions.');
}

/*function devis_preprocess_user_profile(&$vars) {
  $vars['title'] = t('User Account');
}*/

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

/**
 * Implements hook_form_alter().
 */
function devis_form_alter(&$form, &$form_state, $form_id) {
    /*
    // Remember to clear the CACHE before playing with forms.
    dpm($form, 'form');
    dpm($form_state, 'form_state');
    dpm($form_id, 'form_id');
    */
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_comptable_entityform_edit_form_alter(&$form, &$form_state, $form_id) {
    // Amounts.
    $form['field_estimated_annual_revenue']['und'][0]['value']['#attributes']['placeholder'] = '€';
    $form['field_estimated_annual_revenue']['und'][0]['value']['#attributes']['class'][] = 'input-smaller';
    $form['field_estimated_annual_invoice']['und'][0]['value']['#attributes']['class'][] = 'input-smaller';
    $form['field_estimated_number_employees']['und'][0]['value']['#attributes']['class'][] = 'input-smaller';
    
    // Name and Surname on the same line.
    $form['field_prenom']['#prefix'] = '<div class="container-wrapper">';
    $form['field_prenom']['und'][0]['value']['#title'] = t('Surname');
    $form['field_prenom']['und'][0]['value']['#attributes']['placeholder'] = t('Name');
    
    $form['field_name']['und'][0]['value']['#attributes']['placeholder'] = t('Surname');
    $form['field_name']['und'][0]['value']['#title_display'] = 'invisible';
    $form['field_name']['#suffix'] = '</div>';
    
    // Email and telephone in the same line.
    $form['field_email']['#prefix'] = '<div class="container-wrapper">';
    $form['field_email']['und'][0]['email']['#title'] = t('Contact');
    $form['field_email']['und'][0]['email']['#attributes']['placeholder'] = t('E-mail');
    
    $form['field_phone_belgium']['und'][0]['value']['#title_display'] = 'invisible';
    $form['field_phone_belgium']['und'][0]['value']['#attributes']['placeholder'] = t('Telephone');
    $form['field_phone_belgium']['#suffix'] = '</div>';
    
    // Address fields.
    $form['field_adresse']['und'][0]['#prefix'] = '';
    $form['field_adresse']['und'][0]['#suffix'] = '';
    $form['field_adresse']['und'][0]['#title'] = '';
    $form['field_adresse']['und'][0]['#attributes']['class'][] = 'fieldset-hide';
    $form['field_adresse']['und'][0]['street_block']['thoroughfare']['#title'] = t('Address');
    $form['field_adresse']['und'][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
    $form['field_adresse']['und'][0]['street_block']['premise']['#title_display'] = 'invisible';
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_devenir_entityform_edit_form_alter(&$form, &$form_state, $form_id) {
    // Name and Surname on the same line.
    $form['field_prenom']['#prefix'] = '<div class="container-wrapper">';
    $form['field_prenom']['und'][0]['value']['#title'] = t('Surname');
    $form['field_prenom']['und'][0]['value']['#attributes']['placeholder'] = t('Name');
    
    $form['field_name']['und'][0]['value']['#attributes']['placeholder'] = t('Surname');
    $form['field_name']['und'][0]['value']['#title_display'] = 'invisible';
    $form['field_name']['#suffix'] = '</div>';
    
    // Extra validation rules.
    $form['#validate'][] = 'devis_devenir_entityform_edit_form_validate';
    $form['actions']['submit']['#validate'][] = 'devis_devenir_entityform_edit_form_validate';
}

/**
 * Validation for becoming accountant on email.
 */
function devis_devenir_entityform_edit_form_validate($form, &$form_state) {
    if (user_load_by_mail($form_state['values']['field_email']['und'][0]['email'])) {
        $site_name = variable_get('site_name', '3devis.be');
        form_set_error('field_email][und][0][email', t('The specified email is already registered in !site_name.', array('!site_name' => $site_name)));
    }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_user_login_alter(&$form, &$form_state, $form_id) {
    $form['name']['#attributes']['placeholder'] = t('E-mail');
    $form['pass']['#attributes']['placeholder'] = t('Password'); //t('Mot de passe');
    $form['name']['#title'] = t('E-mail');
    $form['name']['#title_display'] = "invisible";
    $form['pass']['#title_display'] = "invisible";
    $form['name']['#description'] = '';
    $form['pass']['#description'] = '';
    $form['name']['#attributes']['class'][] = 'input-login';
    $form['pass']['#attributes']['class'][] = 'input-login';
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_user_pass_alter(&$form, &$form_state, $form_id) {
    $form['name']['#attributes']['placeholder'] = t('E-mail');
    $form['name']['#title'] = t('E-mail');
    $form['name']['#title_display'] = "invisible";
    $form['name']['#description'] = t('Type in your e-mail address and we will send you an e-mail with instructions.');
    $form['name']['#attributes']['class'][] = 'input-login';
    
    $form['actions']['submit']['#value'] = t('Send');
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_user_profile_form_alter(&$form, &$form_state, $form_id) {
  // Name and Surname on the same line.
  $form['field_prenom']['#prefix'] = '<div class="container-wrapper">';
  $form['field_prenom']['und'][0]['value']['#title'] = t('Surname');
  $form['field_prenom']['und'][0]['value']['#attributes']['placeholder'] = t('Name');

  $form['field_name']['und'][0]['value']['#attributes']['placeholder'] = t('Surname');
  $form['field_name']['und'][0]['value']['#title_display'] = 'invisible';
  $form['field_name']['#suffix'] = '</div>';
  
  // Address fields.
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['street_block']['premise']['#title_display'] = 'invisible';
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['country']['#attributes']['style'] = 'display: none;';
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['country']['#title_display'] = 'invisible';
}

function devis_profile2_view_alter($build) {
  /*if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    $build['#post_render'][] = 'my_module_post_render';
  }*/
}

function devis_menu_alter(&$items) {
  
}

function devis_mail_alter(&$message) {
    
}

?>