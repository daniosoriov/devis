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

function devis_preprocess_entity(&$variables, $hook) {
  //dpm($variables, 'variables');
  // good info: http://stackoverflow.com/questions/2383865/how-do-i-use-theme-preprocessor-functions-for-my-own-templates
  // Entityform budget.
  // Change the markup of the submitted information as it is not necessary.
  if ($variables['entity_type'] == 'entityform' && $variables['elements']['#entity_type'] == 'entityform' && $variables['elements']['#bundle'] == 'comptable') {
    $variables['content']['info']['user']['#markup'] = ''; //Submitted by Anonyme on jeu, 06/12/2014 - 14:18
  }
  if ($variables['entity_type'] == 'commerce_order' && $variables['elements']['#entity_type'] == 'commerce_order') {
    // If it's not admin, do not show billing cycle.
    if (!$variables['is_admin']) {
      $variables['content']['cl_billing_cycle']['#access'] = FALSE;
    }
    $user_path = 'user/'. $variables['user']->uid;
    $variables['orders_url'] = l(t('Orders'), $user_path .'/orders');
    $variables['account_url'] = l(t('My account'), $user_path);
  }
}

function devis_entity_view_alter(&$build, $type) {
  // good info: http://drupal.stackexchange.com/questions/40307/how-to-customize-commerce-order-layout
  //dpm($build, 'build');
  switch ($type) {
    case 'commerce_order':
    
      switch ($build['#view_mode']) {
        // Printable version.
        case 'invoice':
          $markup = $build['commerce_line_items'][0]['#markup'];
          $build['commerce_line_items'][0]['#markup'] = str_replace(array('Titre'), array(t('Product')), $markup);
        
          $price = str_replace('.', ',', bcadd($build['commerce_order_total'][0]['#markup'] / 100, 0.00, 2));
          $build['commerce_order_total'][0]['#markup'] = 
            '<table class="order-total"><tbody><tr><td>'. t('Order total') .'</td><td class="views-field-commerce-total">'. $price .' €</td></tr></tbody></table>';
          break;
        
        case 'customer':
          // Put markups inside fieldets so it looks the same as the checkout order.
          $children = $build['commerce_line_items'][0]['#markup'];
          $children .= '<div class="space"></div>';
          $children .= $build['commerce_order_total'][0]['#markup'];
          $children = str_replace(array('Titre', 'Order total'), array(t('Product'), t('Order total')), $children);
          $var = array('element' => array('#children' => $children, '#title' => t('Products')));
          $build['commerce_line_items'][0]['#markup'] = theme_fieldset($var);
          $build['commerce_order_total'][0]['#markup'] = '';

          $children = $build['commerce_customer_billing'][0]['#markup'];
          $var = array('element' => array('#children' => $children, '#title' => t('Billing information')));
          $build['commerce_customer_billing']['#title'] = '';
          $build['commerce_customer_billing'][0]['#markup'] = theme_fieldset($var);
          break;
      
        // good info: https://drupalcommerce.org/discussions/2370/additional-information-order-page
        case 'administrator':
          $order = $build['#entity'];
          $build['status'] = array(
            '#type' => 'fieldset',
            '#title' => t('Order details'),
            '#weight' => -100,
          );
          $build['status']['markup'] = array(
            '#prefix' => '<dl>',
            '#suffix' => '</dl>',
            array('#markup' => sprintf('<dt>%s</dt><dd>%s<dd>', t('Status'), $order->status)),
            array('#markup' => sprintf('<dt>%s</dt><dd>%s<dd>', t('E-mail'), $order->mail)),
          );
          break;
      }
      break;
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
  //dpm($form, 'form');
  
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  
  $form['field_legal_status']['und']['#attributes']['class'][] = 'dropdown';
  $form['field_desired_benefits']['und']['#attributes']['class'][] = 'dropdown';
  $form['field_annual_revenue']['und']['#attributes']['class'][] = 'dropdown';
  $form['field_annual_invoice']['und']['#attributes']['class'][] = 'dropdown';
  $form['field_number_employees']['und']['#attributes']['class'][] = 'dropdown';
  $form['field_acc_most_important']['und']['#attributes']['class'][] = 'dropdown';
  
  // Amounts.
  $form['field_estimated_annual_revenue']['und'][0]['value']['#attributes']['placeholder'] = '€';
  $form['field_estimated_annual_revenue']['und'][0]['value']['#field_prefix'] = '';
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
  
  // Extra submission rules.
  //$form['#submit'][] = 'devis_devenir_entityform_edit_form_submit';
  //$form['actions']['save']['#submit'][] = 'devis_devenir_entityform_edit_form_submit';
}

/**
 * Validation for becoming accountant on email.
 */
function devis_devenir_entityform_edit_form_validate($form, &$form_state) {
  // Check if the email has been registered. Only for new forms.
  if (user_load_by_mail($form_state['values']['field_email']['und'][0]['email']) && isset($form['#entity']->is_new)) {
    $site_name = variable_get('site_name', '3devis.be');
    form_set_error('field_email][und][0][email', t('The specified email is already registered in !site_name.', array('!site_name' => $site_name)));
  }
}

function devis_devenir_entityform_edit_form_submit($form, &$form_state) {
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_checkout_form_checkout_alter(&$form, &$form_state, $form_id) {
  //dpm($form, 'form');
  
  $form['cart_contents']['#title'] = 'Produits';
  $markup = $form['cart_contents']['cart_contents_view']['#markup'];
  $form['cart_contents']['cart_contents_view']['#markup'] = str_replace(array('Order total'), array(t('Order total')), $markup);
  
  $form['customer_profile_billing']['#title'] = t('Billing information');
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['country']['#access'] = FALSE;
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['street_block']['premise']['#title_display'] = 'invisible';
  
  $form['buttons']['#type'] = 'markup';
  $form['buttons']['#prefix'] = '<div class="checkout-buttons">';
  $form['buttons']['#suffix'] = '</div>';
  $form['buttons']['continue']['#value'] = t('Continue');
  $form['buttons']['cancel']['#prefix'] = '';
  $form['buttons']['cancel']['#submit'][] = 'devis_form_commerce_checkout_form_checkout_cancel_submit';
}

function devis_form_commerce_checkout_form_checkout_cancel_submit($form, &$form_state) {
  $form_state['redirect'] = 'user';
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_checkout_form_review_alter(&$form, &$form_state, $form_id) {
  //dpm($form, 'form');
  //dpm($form_state, 'form_state');
  
  //$form['help']['#markup'] = '<div class="checkout-help">'. t('Review your order before continuing.') .'</div>';
  $form['help']['#markup'] = '';
  
  $form['commerce_payment']['payment_method']['#prefix'] = '<div style="display: none;">';
  $form['commerce_payment']['payment_method']['#suffix'] = '</div>';
  
  // Nicer select field.
  //$theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  //$form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  //$form['commerce_payment']['payment_details']['credit_card']['exp_month']['#attributes']['class'][] = 'dropdown';
  
  $cards = $form['commerce_payment']['payment_details']['cardonfile']['#options'];
  if ($cards) {
    foreach ($cards as $id => $val) {
      if (is_numeric($id)) {
        $val = str_replace(array('ending in', 'Expires'), array(t('ending in'), t('Expires')), $val);
        $form['commerce_payment']['payment_details']['cardonfile']['#options'][$id] = $val;
      }
    }
  }
  
  $form['buttons']['#type'] = 'markup';
  $form['buttons']['#prefix'] = '<div class="checkout-buttons">';
  $form['buttons']['#suffix'] = '</div>';
  $form['buttons']['continue']['#value'] = t('Continue');
  $form['buttons']['back']['#prefix'] = '';
}

/**
 * Themes the optional checkout review page data.
 */
function devis_commerce_checkout_review($variables) {
  $content = '';
  //dpm($variables, 'variables');

  foreach ($variables['form']['#data'] as $pane_id => $data) {
    $children = '';
    if ($pane_id == 'cart_contents') {
      $data['title'] = 'Produits';//'Produits à acheter';
    }
    $title = t($data['title']);
    
    // Next, add the data for this particular section.
    if (is_array($data['data'])) {
      // If it's an array, treat each key / value pair accordingly.
      foreach ($data['data'] as $key => $value) {
        $children .= '
          <div class="pane-data">
            <div class="pane-data-key">'. $key .': </div>
            <div class="pane-data-value">'. $value .'</div>
          </div>
        ';
      }
    }
    else {
      if ($pane_id == 'cart_contents') {
        $data['data'] = str_replace(array('Order total'), array(t('Order total')), $data['data']);
      }
      // Otherwise treat it as a block of text in its own row.
      $children = '<div class="pane-data"><div class="pane-data-full">'. $data['data'] .'</div></div>';
    }
    $variables = array(
      'element' => array(
        //'#attributes' => array('class' => array('remove-br')),
        '#children' => $children,
        '#collapsed' => FALSE,
        '#collapsible' => FALSE,
        '#title' => $title,
      ),
    );
    $content .= theme_fieldset($variables);
  }
  
  return $content;
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
  global $user;
  //dpm($form, 'form aqui');
  
  // Deny access to account name.
  $form['account']['name']['#access'] = FALSE;
  
  // Check if $user has the manager role.
  if (in_array('manager', array_values($user->roles)) && $form['#user']->uid == $user->uid) {
    //$form['field_prenom']['#access'] = FALSE;
    //$form['field_name']['#access'] = FALSE;
    //$form['field_company_name']['#access'] = FALSE;
    $form['field_tva']['#access'] = FALSE;
    $form['field_account_activity_status']['#access'] = FALSE;
    $form['field_customer_profile_adresse']['#access'] = FALSE;
    $form['profile_budget_profile']['#access'] = FALSE;
    
    if ($form['#user_category'] == 'budget_profile') {
      $form['actions']['submit']['#access'] = FALSE;
    }
  }
  
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
  
  if ($form['#user_category'] == 'budget_profile') {
    // Nicer select field.
    $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
    $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
    $form['profile_budget_profile']['field_number_budgets']['und']['#attributes']['class'][] = 'dropdown';
    $form['profile_budget_profile']['field_number_budgets']['#attributes']['class'][] = 'dropdown-regular';
    
  }
  
  // Extra validation rules.
  $form['#validate'][] = 'devis_user_profile_form_validate';
  $form['actions']['submit']['#validate'][] = 'devis_user_profile_form_validate';
  
  // Extra submission rules.
  $form['#submit'][] = 'devis_user_profile_form_submit';
  $form['actions']['save']['#submit'][] = 'devis_user_profile_form_submit';
}

function devis_user_profile_form_validate($form, &$form_state) {
  // This is a check in case the javascript is disabled for whatever reason.
  if ($form['#user_category'] == 'budget_profile') {
    $belgium = FALSE;
    $count = 0;
    // Check if Belgium is selected among the choices.
    foreach ($form_state['values']['profile_budget_profile']['field_active_regions_belgium']['und'] as $k => $val) {
      $count++;
      if ($val['value'] == 'BEL') $belgium = TRUE;
    }
    // If Belgium is selected, then asign only Belgium as the value.
    if ($belgium && $count > 1) {
      $new_value = array('und' => array(0 => array('value' => 'BEL')));
      $value['#parents'] = array('profile_budget_profile', 'field_active_regions_belgium'); 
      form_set_value($value, $new_value, $form_state);
    }
  }
}

function devis_user_profile_form_submit($form, &$form_state) {
  $form_state['redirect'] = 'user';
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_stripe_cardonfile_create_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  $form['errors']['#weight'] = -10;
  $form['card-info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Information'),
    '#weight' => 0,
  );
  $form['submit']['#attributes']['class'] = array('card_submit');
  $form['submit']['#suffix'] = l(t('Cancel'), 'user/'. $user->uid .'/cards', array('attributes' => array('class' => array('cancel_url'))));
  $form['submit']['#weight'] = 10;
  $form['credit_card']['cardonfile_instance_default']['#title'] = t('Set as your default card');
  $form['address']['country']['#access'] = FALSE;
  $form['address']['street_block']['thoroughfare']['#title'] = t('Address');
  $form['address']['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['address']['street_block']['premise']['#title_display'] = 'invisible';
  
  $form['card-info']['credit_card'] = $form['credit_card'];
  $form['card-info']['address'] = $form['address'];
  
  unset($form['credit_card'], $form['address']);
}

function devis_form_commerce_cardonfile_card_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  $form['errors']['#weight'] = -10;
  $form['card-info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Information'),
    '#weight' => 0,
  );
  $form['credit_card']['cardonfile_instance_default']['#title'] = t('Set as your default card');
  $form['submit']['#value'] = t('Update');
  $form['submit']['#weight'] = 10;
  $form['submit']['#attributes']['class'] = array('card_submit');
  $form['submit']['#suffix'] = l(t('Cancel'), 'user/'. $user->uid .'/cards', array('attributes' => array('class' => array('cancel_url'))));
  
  $form['card-info']['credit_card'] = $form['credit_card'];
  unset($form['credit_card']);
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