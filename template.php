<?php

// https://www.drupal.org/node/2093811 : Let users edit their customer profile outside of checkout

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
  /*$items['user_pass'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'devis') . '/templates',
    'template' => 'user-pass',
    'preprocess functions' => array(
      'devis_preprocess_user_pass'
    ),
  );*/
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

function devis_preprocess(&$variables, $hook) {
  //dpm($variables, 'variables');
  //dpm($hook, 'hook');
  //dpm(array_keys($variables), 'variables KEYS');
  //dpm($variables['title'], 'title');
  if ($hook == 'entity') {
    //dpm($variables, 'variables');
  }
  if ($hook == 'panels_pane') {
    //dpm($variables, 'variables');
  }
  
  // Remove the ugly title added by default on the user page for the profile.
  if ($hook == 'user_profile_category') {
    if (isset($variables['element']['#title'])) {// && $variables['element']['#title'] == 'Profil de devis souhaité') {
      $variables['title'] = '';
    }
  }
  
  // Add a global variable to jQuery.
  $devenir = array('url' => url('eform/submit/devenir'));
  drupal_add_js(array('devenir' => $devenir), 'setting');
}

function devis_preprocess_user_login(&$variables) {
  $variables['title'] = t('User login');
  $variables['password_url'] = url('user/password');
  $variables['password_label'] = t('Forgot password?');
  $variables['register_url'] = url('eform/submit/devenir');
  $variables['register_label'] = t('Become provider');
}

function devis_preprocess_user_pass(&$variables) {
  //$variables['title'] = t('Forgot password?');
  //$variables['description'] = t('Type in your e-mail address and we will send you an e-mail with instructions.');
}

function devis_preprocess_user_profile(&$variables) {
  //$account = user_load(347);
  //dpm($account, 'account');
  //dpm(user_pass_reset_url($account) .'/login');
  
  $variables['user_profile']['field_prenom']['#access'] = FALSE;
  if (isset($variables['user_profile']['field_name'])) {
    $variables['user_profile']['field_name'][0]['#markup'] = 
      $variables['user_profile']['field_prenom'][0]['#markup'] .' '. $variables['user_profile']['field_name'][0]['#markup'];
  }

  if (isset($variables['user_profile']['field_account_activity_status'])) {
    if (!$variables['user_profile']['field_account_activity_status']['#items'][0]['value']) {
      $variables['user_profile']['field_account_activity_status'][0]['#markup'] = '<span class="inactive">'. $variables['user_profile']['field_account_activity_status'][0]['#markup'] .'</span>';
    }
  }
}

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
  $entity_type = $variables['entity_type'];
  $view_mode = $variables['view_mode'];
  
  // Entityform demander devis.
  if ($entity_type == 'entityform' && $variables['elements']['#entity_type'] == 'entityform' && $variables['elements']['#bundle'] == 'comptable') {
    $entityform = $variables['entityform'];
    $info_list = trois_devis_entity_get_hash_list($entityform->entityform_id);
    
    if (isset($variables['content']['field_provider_contacted']) && $variables['content']['field_provider_contacted']['#access']) {
      foreach ($variables['content']['field_provider_contacted']['#items'] as $key => $arr) {
        if (!$arr['access']) continue;
        $info = $info_list[$arr['target_id']];
        $variables['content']['field_provider_contacted'][$key]['#markup'] .= ' -- '. 
          l('User URL', 'devis_info/'. $info->url_info, array('attributes' => array('target' => '_blank', 'title' => 'This is the secure URL for the user to see the budget...', 'onclick' => 'return false'))) .' - '.
          (($info->date) ? 'viewed on '. format_date($info->date, 'medium', '', NULL, 'en') : '<em>not yet viewed</em>') .'';
      }
    }
    
    if (isset($variables['content']['field_company_name'])) {
      $variables['content']['field_company_name']['#title'] = t('Société');
    }
    $variables['content']['field_tva']['#access'] = $variables['elements']['field_tva']['#access'] = TRUE;
    if ($variables['content']['field_legal_status']['#items'][0]['value'] == 'association') {
      $variables['content']['field_company_name']['#title'] = t('Association');
      //$variables['content']['field_tva']['#title'] = t('Numéro de TVA de votre association');
    }
  }
  
  // Commerce Order view.
  if ($entity_type == 'commerce_order' && $variables['elements']['#entity_type'] == 'commerce_order') {
    $variables['content']['commerce_order_total']['#title'] = t('Total');
    $variables['elements']['commerce_order_total']['#title'] = t('Total');
    // If it's not admin, do not show billing cycle.
    if (!$variables['is_admin']) {
      $variables['content']['cl_billing_cycle']['#access'] = FALSE;
    }
    $user_path = 'user/'. $variables['user']->uid;
    $variables['orders_url'] = l(t('Invoices'), $user_path .'/orders');
    $variables['account_url'] = l(t('My account'), $user_path);
  }
  
  // Commerce Order PDF.
  $pdf_view_modes = array('pdf', 'canceled');
  if ($entity_type == 'commerce_order' && in_array($view_mode, $pdf_view_modes)) {
    //$variables['theme_hook_suggestions'][] = $entity_type . '__commerce_order__' . $view_mode;
    $order = $variables['commerce_order'];
    $variables['content']['order_number']['#markup'] = t('Invoice') .': '. $order->order_number;
    $variables['content']['order_id']['#markup'] = t('Commande') .': '. $order->order_id;
    
    $markup = $variables['content']['commerce_line_items'][0]['#markup'];
    $variables['content']['commerce_line_items'][0]['#markup'] = str_replace(array('Titre'), array(t('Product')), $markup);
    $markup = $variables['content']['commerce_order_total'][0]['#markup'];
    $variables['content']['commerce_order_total'][0]['#markup'] = str_replace(array('Order total'), array(t('Order total')), $markup);
  }
  
  if ($entity_type == 'profile2' && $variables['elements']['#entity_type'] == 'profile2') {
    $count = $variables['content']['field_contacted_this_month']['#items'][0]['value'];
    $variables['content']['field_contacted_this_month'][0]['#markup'] = ($count) ? format_plural($count, '1 time', '@count times') : t('0 times');
    $count = $variables['content']['field_contacted_total']['#items'][0]['value'];
    $variables['content']['field_contacted_total'][0]['#markup'] = ($count) ? format_plural($count, '1 time', '@count times') : t('0 times');
    
    // Change the list of budgets layout for the viewed user.
    if (isset($variables['content']['field_entity_devis_ref']['#items'])) {
      $info_list = trois_devis_user_get_hash_list($variables['elements']['#entity']->uid);
      foreach ($variables['content']['field_entity_devis_ref']['#items'] as $key => $arr) {
        if (!$arr['access']) continue;
        $entity = $arr['entity'];
        $info = $info_list[$entity->entityform_id];
        
        $temp = array_keys($entity->field_prenom);
        $lang_prenom = array_shift($temp);
        $temp = array_keys($entity->field_name);
        $lang_name = array_shift($temp);
        $temp = array_keys($entity->field_company_name);
        $lang_company = array_shift($temp);
        
        $markup = l($entity->entityform_id, 'entityform/'. $entity->entityform_id) .' - '. 
          $entity->field_prenom[$lang_prenom][0]['safe_value'] .' '.
          $entity->field_name[$lang_name][0]['safe_value'] .' - '.
          $entity->field_company_name[$lang_company][0]['safe_value'] .' - '.
          format_date($entity->created, 'medium', '', NULL, 'en') .' -- '.
          l('User URL', 'devis_info/'. $info->url_info, array('attributes' => array('target' => '_blank', 'title' => 'This is the secure URL for the user to see the budget...', 'onclick' => 'return false'))) .' - '.
          (($info->date) ? 'viewed on '. format_date($info->date, 'medium', '', NULL, 'en') : '<em>not yet viewed</em>');

        $variables['content']['field_entity_devis_ref'][$key]['#markup'] = $markup;
      }
    }
  }
}

/**
 * Implements theme_preprocess_fieldset().
 */
function devis_preprocess_fieldset(&$variables) {
  /*if (isset($variables['element']['#id']) && $variables['element']['#id'] == 'edit-legal') {
    $variables['element']['#title'] = t('Termes et conditions');
  }*/
}

/**
 * Implements theme_preprocess_panels_pane().
 */
function devis_preprocess_panels_pane(&$variables) {
  // Beautify ask for a password.
  if (isset($variables['content']['system_main']['#id']) && $variables['content']['system_main']['#id'] == 'user-pass') {
    $variables['content']['system_main']['name']['#access'] = FALSE;
    $variables['title'] = t('Forgot password?');
    $variables['description'] = t('Type in your e-mail address and we will send you an e-mail with instructions.');
  }
  
  // Terms and conditions.
  if (isset($variables['content']['system_main']['legal']) && strcmp($variables['title'], 'Terms and Conditions') === 0) {
    $variables['title'] = $variables['content']['system_main']['legal']['#title'] = t('Comment ça marche - Combien ça coûte');//t('Termes et conditions');
  }
  
  // Changes for the entityforms.
  if (isset($variables['content']['system_main']['entityform'])) {
    $array = array_values($variables['content']['system_main']['entityform']);
    $element = array_shift($array);
    $entityform_id = $element['#entity']->entityform_id;
    if ($element['#bundle'] == 'comptable') {
      $variables['title'] = t('Budget request @id', array('@id' => $entityform_id));
    }
    elseif ($element['#bundle'] == 'devenir') {
      $variables['title'] = t('Provider request @id', array('@id' => $entityform_id));
    }
    $variables['content']['system_main']['entityform'][$entityform_id]['info']['user']['#markup'] = 'Submitted on '. format_date($element['#entity']->created, 'medium', '', NULL, 'en');
  }
  
  // Modify user name on title.
  if (isset($variables['content']['system_main']['profile_budget_profile']) && $variables['content']['system_main']['#theme'] != 'user_profile') {
    $company_name = '';
    $user = $variables['content']['system_main']['#user'];
    if (!empty($user->field_company_name)) {
      $temp = array_keys($user->field_company_name);
      $lang = array_shift($temp);
      $company_name = $user->field_company_name[$lang][0]['safe_value'];
    }
    if ($company_name) {
      $variables['title'] = $company_name .' - '. $variables['title'];
    }
  }
  
  // Commerce Order.
  if (isset($variables['content']['system_main']['commerce_order'])) {
    // Fix some titles.
    $title = $variables['content']['metatags']['global']['title']['#attached']['metatag_set_preprocess_variable'][0][2];
    $title = str_replace('Order', t('Invoice'), $title);
    $variables['content']['metatags']['global']['title']['#attached']['metatag_set_preprocess_variable'][0][2] =
    $variables['content']['metatags']['global']['title']['#attached']['metatag_set_preprocess_variable'][1][2]['title'] = $title;
    $variables['title'] = str_replace('Order', t('Invoice'), $variables['title']);
  }
  
  if (isset($variables['content']['system_main']['#entity_type'])) {
    $entity_type = $variables['content']['system_main']['#entity_type'];
    switch ($entity_type) {
      // Change display of user name on their profile.
      // Change some descriptions.
      case 'user':
        $company_name = '';
        // User profile.
        if (isset($variables['content']['system_main']['#theme']) && $variables['content']['system_main']['#theme'] == 'user_profile') {
          if (isset($variables['content']['system_main']['field_company_name'])) {
            $company_name = $variables['content']['system_main']['field_company_name'][0]['#markup'];
          }
        }
        // User profile edit.
        if (isset($variables['content']['system_main']['#id']) && $variables['content']['system_main']['#id'] == 'user-profile-form') {
          $variables['content']['system_main']['account']['mail']['#description'] = 
            $variables['content']['system_main']['account']['mail']['mail']['#description'] = '';
          if (isset($variables['content']['system_main']['account']['current_pass'])) {
            $desc = $variables['content']['system_main']['account']['current_pass']['#description'];
            $desc = strip_tags(str_replace('<a', '<br /><a', $desc), '<br><a>');
            $variables['content']['system_main']['account']['current_pass']['#description'] = $desc;
          }
          
          $lang = $variables['content']['system_main']['field_company_name']['#language'];
          $company_name = $variables['content']['system_main']['field_company_name'][$lang][0]['value']['#default_value'];
        }
        if ($company_name && strpos($variables['title'], $company_name) === false) {
          $variables['title'] = $company_name .' - '. $variables['title'];
        }
        // If the admin is viewing his own profile, hide the desired profile.
        if (isset($variables['content']['system_main']['#account'])) {
          $account = $variables['content']['system_main']['#account'];
          $user = $variables['user'];
          if ($account->uid == $user->uid && in_array('manager', $user->roles)) {
            $variables['content']['system_main']['profile_budget_profile']['#access'] = FALSE;
          }
        }
      break;
      
      case 'commerce_customer_profile':
        // Add a class to fix the TVA value being in another fieldset.
        if (isset($variables['content']['system_main']['commerce_customer_address'])) {
          $variables['content']['system_main']['commerce_customer_address'][LANGUAGE_NONE][0]['#attributes']['class'][] = 'group-address-top';
        }
      break;
      
      // Changes for the entityforms edit/submission.
      case 'entityform':
        $entityform = $variables['content']['system_main']['#entity'];
        $entityform_id = $entityform->entityform_id;
        if ($entityform_id) {
          if ($variables['content']['system_main']['#bundle'] == 'comptable') {
            $variables['title'] = t('Budget request @id', array('@id' => $entityform_id));
          }
          elseif ($variables['content']['system_main']['#bundle'] == 'devenir') {
            $variables['title'] = t('Provider request @id', array('@id' => $entityform_id));
          }
          $variables['content']['system_main']['user_info']['#markup'] = 'Submitted on '. format_date($entityform->created, 'medium', '', NULL, 'en');

          $variables['content']['system_main']['intro']['#markup'] = '';
        }
        else {
          $messages = theme_status_messages(array('display' => ''));
          $variables['content']['system_main']['intro']['#markup'] .= '<div id="messages-box">'. $messages .'</div>';
        }
      break;
    }
  }
  
  if (isset($variables['user']) && is_object($variables['user']) && isset($variables['content']['system_main']['main'])) {
    $path = current_path();
    // Address Book.
    $uid = $variables['user']->uid;
    if ($path == 'user/'. $uid .'/addressbook/billing') {
      $variables['title'] = t('Coordonnées de facturation');
      $variables['content']['system_main']['main']['#markup'] = str_replace('>edit<', '>'. t('edit') .'<', $variables['content']['system_main']['main']['#markup']);
      if (isset($variables['content']['system_main']['main']['#markup']) && strpos($variables['content']['system_main']['main']['#markup'], 'addressbook-nodata') !== false) {
        $variables['content']['system_main']['main']['#markup'] = '<div class="addressbook-nodata">' . t('Votre coordonnées de facturation est actuellement vide.') . '</div>';
      }
    }
    // Credit cards.
    if ($path == 'user/'. $uid .'/cards') {
      $variables['title'] = t('Credit cards');
    }
    // Orders.
    if ($path == 'user/'. $uid .'/orders') {
      $variables['title'] = t('Invoices');
    }
  }
}

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

function devis_entity_view_alter(&$build, $type) {
  // good info: http://drupal.stackexchange.com/questions/40307/how-to-customize-commerce-order-layout
  //dpm($build, 'build');
  //dpm($type, 'type');
  switch ($type) {
    case 'user':
      break;
    
    case 'commerce_order':
      switch ($build['#view_mode']) {
        case 'pdf':
          $markup = $build['commerce_line_items'][0]['#markup'];
          $build['commerce_line_items'][0]['#markup'] = str_replace(array('Titre'), array(t('Product')), $markup);
          // An idea to fix the problem of the table is to create the table again from here using theme_table.
          break;
        
        case 'customer':
          // Line items was giving me problems as they were not being displayed. 
          // In order to fix this, I changed the query rewritting to FALSE (View > Advanced > Query settings) on the line item view.
          // This is suggested here: https://www.drupal.org/node/1541206#comment-9103957
        
          // Put markups inside fieldets so it looks the same as the checkout order.
          $children = $build['commerce_line_items'][0]['#markup'];
          $children .= '<div class="space"></div>';
          $children .= $build['commerce_order_total'][0]['#markup'];
          $children = str_replace('Order total', t('Total'), $children);
          $var = array('element' => array('#children' => $children, '#title' => t('Products')));
          $build['commerce_line_items'][0]['#markup'] = theme_fieldset($var);
          $build['commerce_order_total'][0]['#markup'] = '';

          $children = '<h3 class="field-label">'. t('Address') .'</h3>';
          $children .= $build['commerce_customer_billing'][0]['#markup'];
          // Show invoice date on the same fieldset than the address.
          if (isset($build['field_commerce_billy_i_date'])) {
            $children .= '<h3 class="field-label">'. t('Invoice date') .'</h3>';
            $children .= '<div class="field-date">'. $build['field_commerce_billy_i_date'][0]['#markup'] .'</div>';
            $build['field_commerce_billy_i_date']['#access'] = FALSE;
          }
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
            array('#markup' => sprintf('<dt>%s</dt><dd>%s<dd>', 'E-mail', $order->mail)),
          );
          break;
        
        case 'pdf':
          break;
      }
      break;
  }
}

/**
 * Implements hook_form_alter().
 */
function devis_form_alter(&$form, &$form_state, $form_id) {
  
  // Remember to clear the CACHE before playing with forms.
  //dpm($form, 'form');
  //dpm($form_state, 'form_state');
  //dpm($form_id, 'form_id');
  
  switch ($form_id) {
    case 'commerce_cardonfile_card_form':
      $form['#validate'][] = 'devis_form_alter_validate';
      //$form['actions']['submit']['#validate'][] = 'devis_form_alter_validate';
      break;
  }
}

function devis_form_alter_validate(&$form_state, $form) {
  //dpm($form, 'form VALIDATE');
  //dpm($form_state, 'form_state');
  //dpm($_SESSION['messages']['error'], 'errors');
  if (isset($_SESSION['messages']['error']) && count($_SESSION['messages']['error']) > 0) {
    $errors = &$_SESSION['messages']['error'];
    //dpm($errors, 'errors inside');
    foreach ($errors as $item => $message) {
      switch ($message) {
        case 'You have specified an expired credit card.':
          $errors[$item] = t('Vous avez spécifié une carte de crédit qui a expiré.');
          break;
      }
    }
    if ($form['build_info']['form_id'] == 'legal_login') {
      if (isset($errors[0]) && $errors[0] != '' && count($errors) == 1) {
        $errors[0] = t('Vous devez accepter nos termes et conditions pour continuer.');
      }
    }
  }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_comptable_entityform_edit_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  if (in_array('provider', $user->roles)) {
    drupal_set_message(t('AVIS: Vous êtes fournisseur. Vous ne pouvez pas faire une demande de devis.'), 'warning');
    $form['actions']['submit']['#access'] = FALSE;
  }
  
  if (in_array('manager', $user->roles) && !isset($form_state['build_info']['args'][0]->is_new)) {
    $entity = $form_state['build_info']['args'][0];
    $mail = $entity->field_email[$form['field_email']['#language']][0]['email'];
    $temp = array_keys($entity->field_approval);
    $lang = array_shift($temp);
    $approval = $entity->field_approval[$lang][0]['value'];
    // If the request has been already approved or denied.
    if ($approval != 'pending') {
      drupal_set_message(t('Notice: This request can no longer be modified.'), 'warning');
      $form['actions']['#access'] = FALSE;
    }
    $form_state['set_redirect'] = TRUE;
  }
  
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  $form['#attached']['css'][] = $theme_path .'/css/easydropdown.css';
  
  $form['field_legal_status'][$form['field_legal_status']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_desired_benefits'][$form['field_desired_benefits']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_annual_revenue'][$form['field_annual_revenue']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_annual_invoice'][$form['field_annual_invoice']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_number_employees'][$form['field_number_employees']['#language']]['#attributes']['class'][] = 'dropdown';
  
  // Amounts.
  $lang = $form['field_estimated_annual_revenue']['#language'];
  $form['field_estimated_annual_revenue'][$lang][0]['value']['#attributes']['placeholder'] = '€';
  $form['field_estimated_annual_revenue'][$lang][0]['value']['#field_prefix'] = '';
  $form['field_estimated_annual_revenue'][$lang][0]['value']['#attributes']['class'][] = 'input-smaller';
  $form['field_estimated_annual_invoice'][$lang][0]['value']['#attributes']['class'][] = 'input-smaller';
  $lang = $form['field_estimated_number_employees']['#language'];
  $form['field_estimated_number_employees'][$lang][0]['value']['#attributes']['class'][] = 'input-smaller';

  // Name and Surname on the same line.
  // Honorific, Name and Surname on the same line.
  $lang = $form['field_honorific']['#language'];
  $label = '<label for="edit-field-honorific-'. $lang .'">'. t('Last name') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['field_honorific']['#prefix'] = '<div class="container-wrapper">'. $label;
  $form['field_honorific'][$lang]['#options'] = array('_none' => t('Civilité')) + $form['field_honorific'][$lang]['#options'];
  $form['field_honorific'][$lang]['#title'] = t('Civilité');
  $form['field_honorific'][$lang]['#title_display'] = 'invisible';
  $form['field_honorific'][$lang]['#attributes']['class'][] = 'dropdown';
  $form['field_honorific']['#attributes']['class'][] = 'dropdown-honorific';
  
  $lang = $form['field_prenom']['#language'];
  $form['field_prenom'][$lang][0]['value']['#attributes']['placeholder'] = 
  $form['field_prenom'][$lang][0]['value']['#title'] = t('First name');
  $form['field_prenom'][$lang][0]['value']['#title_display'] = 'invisible';

  $lang = $form['field_name']['#language'];
  $form['field_name'][$lang][0]['value']['#attributes']['placeholder'] = 
  $form['field_name'][$lang][0]['value']['#title'] = t('Last name');
  $form['field_name'][$lang][0]['value']['#title_display'] = 'invisible';
  $form['field_name']['#suffix'] = '</div>';
  
  // Email and telephone in the same line.
  $lang = $form['field_email']['#language'];
  $label = '<label for="edit-field-email-'. $lang .'">'. t('Contact') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['field_email']['#prefix'] = '<div class="container-wrapper">'. $label;
  $form['field_email'][$lang][0]['email']['#title'] = 'E-mail';
  $form['field_email'][$lang][0]['email']['#title_display'] = 'invisible';
  $form['field_email'][$lang][0]['email']['#attributes']['placeholder'] = 'E-mail';
  $form['field_email'][$lang][0]['email']['#attributes']['class'][] = 'email-input-class';

  $lang = $form['field_phone_belgium']['#language'];
  $form['field_phone_belgium'][$lang][0]['value']['#title_display'] = 'invisible';
  $form['field_phone_belgium'][$lang][0]['value']['#attributes']['placeholder'] = t('Telephone');
  $form['field_phone_belgium']['#suffix'] = '</div>';
  
  // Website.
  $form['#after_build'][] = 'devis_form_comptable_entityform_edit_form_after_build';

  // Address fields.
  $lang = $form['field_adresse']['#language'];
  $form['field_adresse'][$lang][0]['#prefix'] = '';
  $form['field_adresse'][$lang][0]['#suffix'] = '';
  $form['field_adresse'][$lang][0]['#title'] = '';
  $form['field_adresse'][$lang][0]['#attributes']['class'][] = 'fieldset-hide';
  $form['field_adresse'][$lang][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['field_adresse'][$lang][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['field_adresse'][$lang][0]['street_block']['premise']['#title_display'] = 'invisible';
  
  // Extra validation rules.
  $form['#validate'][] = 'devis_comptable_entityform_edit_form_validate';
  $form['actions']['submit']['#validate'][] = 'devis_comptable_entityform_edit_form_validate';
  // Extra submission rules
  $form['#submit'][] = 'devis_comptable_entityform_edit_form_submit';
  $form['actions']['save']['#submit'][] = 'devis_comptable_entityform_edit_form_submit';
}

function devis_comptable_entityform_edit_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  $legal_status = $values['field_legal_status'][$form['field_legal_status']['#language']][0]['value'];
  
  // Do not go forward if there are no providers to contact and is being accepted.
  $lang = $form['field_approval']['#language'];
  if ($values['field_approval'][$lang][0]['value'] == 'approved') {
    $result = views_get_view_result('provider', 'accountants_to_contact', $legal_status);
    if (empty($result)) {
      form_set_error('field_approval]['. $lang .'][0][value', t('This budget request cannot be approved yet. There are no providers to contact which match the specified parameters.'));
    }
  }
  
  // Postal code validation.
  if (module_exists('postal_code_validation')) {
    $lang = $form['field_adresse']['#language'];
    $address = $values['field_adresse'][$lang][0];
    $postal_code = $address['postal_code'];
    $country = $address['country'];
    $result = postal_code_validation_validate($postal_code, $country);
    if ($result['error']) {
      form_set_error('field_adresse]['. $lang .'][0][postal_code', $result['error']);
    }
  }
  
  $lang = $form['field_estimated_annual_revenue']['#language'];
  $value = $values['field_estimated_annual_revenue'][$lang][0]['value'];
  if (isset($value)) $value = trim($value);
  if ($values['field_annual_revenue'][$form['field_annual_revenue']['#language']][0]['value'] == 'nonexistent' && !$value) {
    $label = $form['field_estimated_annual_revenue'][$lang]['#title'];
    form_set_error('field_estimated_annual_revenue]['. $lang .'][0][value', t('Le champ !label est requis.', array('!label' => $label)));
  }
  $lang = $form['field_estimated_annual_invoice']['#language'];
  $value = $values['field_estimated_annual_invoice'][$lang][0]['value'];
  if (isset($value)) $value = trim($value);
  if ($values['field_annual_invoice'][$form['field_annual_invoice']['#language']][0]['value'] == 'nonexistent' && !$value) {
    $label = $form['field_estimated_annual_invoice'][$lang]['#title'];
    form_set_error('field_estimated_annual_invoice]['. $lang .'][0][value', t('Le champ !label est requis.', array('!label' => $label)));
  }
  $lang = $form['field_estimated_number_employees']['#language'];
  $value = $values['field_estimated_number_employees'][$lang][0]['value'];
  if (isset($value)) $value = trim($value);
  if ($values['field_number_employees'][$form['field_number_employees']['#language']][0]['value'] == 'nonexistent' && !$value) {
    $label = $form['field_estimated_number_employees'][$lang]['#title'];
    form_set_error('field_estimated_number_employees]['. $lang .'][0][value', t('Le champ !label est requis.', array('!label' => $label)));
  }
  
  $lang = $form['field_company_name']['#language'];
  $lang_tva = $form['field_tva']['#language'];
  $company = $values['field_company_name'][$lang][0]['value'];
  if (isset($company)) $company = trim($company);
  $tva = $values['field_tva'][$lang_tva][0]['value'];
  if (isset($tva)) $tva = trim($tva);
  $company_error = $tva_error = FALSE;
  $company_label = $form['field_company_name'][$lang]['#title'];
  $tva_label = $form['field_tva'][$lang_tva]['#title'];
  switch ($legal_status) {
    case 'association':
      if (!$company) {
        $company_label = 'Nom de votre association';
        $company_error = TRUE;
      }
      if (!$tva) {
        $tva_label = 'Numéro de TVA de votre association';
        $tva_error = TRUE;
      }
      break;
    
    case 'society':
      if (!$company) $company_error = TRUE;
      if (!$tva) $tva_error = TRUE;
      break;
    
    case 'independent':
      if (!$tva) $tva_error = TRUE;
      break;
  }
  if ($company_error) {
    form_set_error('field_company_name]['. $lang .'][0][value', t('Le champ !label est requis.', array('!label' => $company_label)));
  }
  if ($tva_error) {
    form_set_error('field_tva]['. $lang_tva .'][0][value', t('Le champ !label est requis.', array('!label' => $tva_label)));
  }
}

function devis_comptable_entityform_edit_form_submit($form, &$form_state) {
  if (isset($form_state['set_redirect'])) {
    $form_state['redirect'] = 'adminpage/request/budget';
  }
}

function devis_form_comptable_entityform_edit_form_after_build($form, &$form_state) {
  //$form['field_website'][$form['field_website']['#language']][0]['url']['#attributes']['placeholder'] = 'www.siteweb.com';
  
  // JS variables for jQuery.
  $lang = $form['field_company_name']['#language'];
  $lang_tva = $form['field_tva']['#language'];
  $company = $form['field_company_name'][$lang];
  $tva = $form['field_tva'][$lang_tva];
  $req_span = ' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span>';
  $devenir = array(
    'companyTitle' => $company['#title'],
    'tvaTitle' => $tva['#title'],
    'required' => $req_span,
  );
  drupal_add_js(array('devenir' => $devenir), 'setting');
  
  if ($form['field_legal_status'][$form['field_legal_status']['#language']]['#value'] == 'association') {
    $form['field_company_name']['#title'] = 
      $form['field_company_name'][$lang]['#title'] = t('Nom de votre association');
    $form['field_tva']['#title'] = 
      $form['field_tva'][$lang_tva]['#title'] = t('Numéro de TVA de votre association');
  }
  return $form;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_devenir_entityform_edit_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  if (in_array('provider', $user->roles)) {
    drupal_set_message(t('AVIS: Vous êtes déjà fournisseur.'), 'warning');
    $form['actions']['submit']['#access'] = FALSE;
  }
  
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  $form['#attached']['css'][] = $theme_path .'/css/easydropdown.css';

  // Honorific, Name and Surname on the same line.
  $lang = $form['field_honorific']['#language'];
  $label = '<label for="edit-field-honorific-'. $lang .'">'. t('Last name') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['field_honorific']['#prefix'] = '<div class="container-wrapper">'. $label;
  $form['field_honorific'][$lang]['#options'] = array('_none' => t('Civilité')) + $form['field_honorific'][$lang]['#options'];
  $form['field_honorific'][$lang]['#title'] = t('Civilité');
  $form['field_honorific'][$lang]['#title_display'] = 'invisible';
  $form['field_honorific'][$lang]['#attributes']['class'][] = 'dropdown';
  $form['field_honorific']['#attributes']['class'][] = 'dropdown-honorific';
  
  $lang = $form['field_prenom']['#language'];
  $form['field_prenom'][$lang][0]['value']['#attributes']['placeholder'] = 
  $form['field_prenom'][$lang][0]['value']['#title'] = t('First name');
  $form['field_prenom'][$lang][0]['value']['#title_display'] = 'invisible';

  $lang = $form['field_name']['#language'];
  $form['field_name'][$lang][0]['value']['#attributes']['placeholder'] = 
  $form['field_name'][$lang][0]['value']['#title'] = t('Last name');
  $form['field_name'][$lang][0]['value']['#title_display'] = 'invisible';
  $form['field_name']['#suffix'] = '</div>';
  
  // Website.
  $form['#after_build'][] = 'devis_form_devenir_entityform_edit_form_after_build';
  
  $lang = $form['field_info_extra']['#language'];
  $desc = $form['field_info_extra'][$lang][0]['value']['#description'];
  $form['field_info_extra'][$lang][0]['value']['#attributes']['placeholder'] = $desc;//t('@description', array('@description' => $desc));
  $form['field_info_extra'][$lang][0]['value']['#description'] = '';
  
  // If the request is being checked by a manager, so it is not new.
  if (in_array('manager', array_values($user->roles)) && !isset($form_state['build_info']['args'][0]->is_new)) {
    $entity = $form_state['build_info']['args'][0];
    $mail = $entity->field_email[$form['field_email']['#language']][0]['email'];
    $temp = array_keys($entity->field_approval);
    $lang = array_shift($temp);
    $approval = $entity->field_approval[$lang][0]['value'];
    // If the mail of the request is already registered.
    if ($approval == 'pending') {
      $account = user_load_by_mail($mail);
      if ($account) {
        drupal_set_message(t('Notice: The requester is already registered in the system: !user', array('!user' => theme('username', array('account' => $account)))), 'warning');
        $form['actions']['#access'] = FALSE;
      }
    }
    // If the request has been already approved or denied.
    else {
      drupal_set_message(t('Notice: This request can no longer be modified.'), 'warning');
      $form['actions']['#access'] = FALSE;
    }
    $form_state['set_redirect'] = TRUE;
  }

  // Extra validation rules.
  $form['#validate'][] = 'devis_devenir_entityform_edit_form_validate';
  $form['actions']['submit']['#validate'][] = 'devis_devenir_entityform_edit_form_validate';
  
  // Extra submission rules.
  $form['#submit'][] = 'devis_devenir_entityform_edit_form_submit';
  $form['actions']['save']['#submit'][] = 'devis_devenir_entityform_edit_form_submit';
}

/**
 * Validation for becoming accountant.
 */
function devis_devenir_entityform_edit_form_validate($form, &$form_state) {
  // Check if the email has been registered. Only for new forms.
  $lang = $form['field_email']['#language'];
  if (user_load_by_mail($form_state['values']['field_email'][$lang][0]['email']) && isset($form['#entity']->is_new)) {
    $site_name = variable_get('site_name', '3devis.be');
    form_set_error('field_email]['. $lang .'][0][email', t('The specified email is already registered in !site_name.', array('!site_name' => $site_name)));
  }
}

function devis_devenir_entityform_edit_form_submit($form, &$form_state) {
  if (isset($form_state['set_redirect'])) {
    $form_state['redirect'] = 'adminpage/request/provider';
  }
}

function devis_form_devenir_entityform_edit_form_after_build($form, &$form_state) {
  $form['field_website'][$form['field_website']['#language']][0]['url']['#attributes']['placeholder'] = 'www.siteweb.com';
  return $form;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_checkout_form_checkout_alter(&$form, &$form_state, $form_id) {
  /*global $user;
  $account = user_load($user->uid);
  
  $form['cart_contents']['#title'] = 'Produits';
  $markup = $form['cart_contents']['cart_contents_view']['#markup'];
  $form['cart_contents']['cart_contents_view']['#markup'] = str_replace(array('Order total'), array(t('Order total')), $markup);
  
  $form['account']['username']['#markup'] = $account->field_prenom['und'][0]['safe_value'] .' '. $account->field_name['und'][0]['safe_value'];
  */
  
  $form['customer_profile_billing']['#title'] = t('Coordonnées de facturation');
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['country']['#access'] = FALSE;
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['customer_profile_billing']['commerce_customer_address']['und'][0]['street_block']['premise']['#title_display'] = 'invisible';
  
  $form['help']['#markup'] = '';
  
  $form['commerce_payment']['#title'] = t('Credit card details');//t('Détails de votre carte de crédit');
  $form['commerce_payment']['payment_method']['#prefix'] = '<div style="display: none;">';
  $form['commerce_payment']['payment_method']['#suffix'] = '</div>';
  $form['commerce_payment']['payment_details']['credit_card']['exp_month']['#title'] = t('Expiration date');
  $form['commerce_payment']['payment_details']['credit_card']['exp_month']['#title_display'] = 'invisible';
  $form['commerce_payment']['payment_details']['credit_card']['exp_month']['#prefix'] .= '<label for="edit-commerce-payment-payment-details-credit-card-exp-month">'. t('Expiration date') .'</label>';
  
  if (isset($form['commerce_payment']['payment_details']['cardonfile']['#options'])) {
    $cards = $form['commerce_payment']['payment_details']['cardonfile']['#options'];
    if ($cards) {
      foreach ($cards as $id => $val) {
        if (is_numeric($id)) {
          $val = str_replace(array('ending in', 'Expires'), array(t('ending in'), t('Expires')), $val);
          $form['commerce_payment']['payment_details']['cardonfile']['#options'][$id] = $val;
        }
      }
    }
  }
  
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
  $form['buttons']['#type'] = 'markup';
  $form['buttons']['#prefix'] = '<div class="checkout-buttons">';
  $form['buttons']['#suffix'] = '</div>';
  $form['buttons']['continue']['#value'] = t('Continue');
  $form['buttons']['cancel']['#prefix'] = '';
  $form['buttons']['cancel']['#submit'][] = 'devis_form_commerce_checkout_form_checkout_cancel_submit';
  //$form['buttons']['back']['#prefix'] = '';
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
  $form['name']['#attributes']['placeholder'] = 'E-mail';
  $form['pass']['#attributes']['placeholder'] = t('Password');
  $form['name']['#title'] = 'E-mail';
  $form['name']['#title_display'] = "invisible";
  $form['pass']['#title_display'] = "invisible";
  $form['name']['#description'] = '';
  $form['pass']['#description'] = '';
  $form['info'] = array(
    '#type' => 'fieldset',
    '#weight' => -10,
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['info']['name'] = $form['name'];
  $form['info']['pass'] = $form['pass'];
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_user_pass_alter(&$form, &$form_state, $form_id) {
  $form['info'] = array(
    '#type' => 'fieldset',
    '#weight' => -10,
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['name']['#attributes']['placeholder'] = 'E-mail';
  $form['name']['#title'] = 'E-mail';
  $form['name']['#title_display'] = "invisible";
  $form['name']['#description'] = t('Type in your e-mail address and we will send you an e-mail with instructions.');
  $form['info']['name'] = $form['name'];
  $form['actions']['submit']['#value'] = t('Send');
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_user_profile_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  // If coming to set the password for the first time or to reset it,
  // then change some fields in the layout.
  $path = current_path();
  if (strpos($_SERVER['QUERY_STRING'], 'pass-reset-token') !== false && $path == 'user/'. $user->uid .'/edit') {
    $form['account']['current_pass']['#access'] = FALSE;
    $form['account']['pass']['#description'] = t('Saisissez le nouveau mot de passe dans les deux champs de texte.');
    
    // If using the first login, remove the delete account button.
    if (!profile2_load_by_user($user)) {
      $form['actions']['cancel']['#access'] = FALSE;
    }
  }
  
  $form['account']['#type'] = 'fieldset';
  $form['account']['#title'] = t('Settings');
  $form['legal']['#access'] = FALSE;
  
  // Nicer select field.
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  $form['#attached']['css'][] = $theme_path .'/css/easydropdown.css';
  
  // Deny access to account name.
  $form['account']['name']['#access'] = FALSE;
  $form['contact']['#access'] = FALSE;
  
  // Check if $user has the manager role.
  if (in_array('manager', array_values($user->roles)) && $form_state['user']->uid == $user->uid) {
    if ($form['#user_category'] == 'account') {
      //$form['field_prenom']['#access'] = FALSE;
      //$form['field_name']['#access'] = FALSE;
      //$form['field_company_name']['#access'] = FALSE;
      $form['field_preferred_language']['#access'] = FALSE;
      $form['field_tva']['#access'] = FALSE;
      $form['field_website']['#access'] = FALSE;
      $form['field_account_activity_status']['#access'] = FALSE;
      $form['field_customer_profile_adresse']['#access'] = FALSE;
      $form['profile_budget_profile']['#access'] = FALSE;
    }
    
    /*if ($form['#user_category'] == 'budget_profile') {
      $form['profile_budget_profile']['#access'] = FALSE;
      $form['actions']['submit']['#access'] = FALSE;
    }*/
  }
  
  if ($form['#user_category'] == 'account') {
    // If user is manager.
    if (in_array('manager', array_values($user->roles))) {
      $form['account']['mail']['htmlmail_plaintext']['#access'] = FALSE;
      $form['account']['mail']['mail']['#title'] = 'E-mail';
      $form['account']['mail']['mail']['#description'] = str_replace('courriels', 'emails', $form['account']['mail']['mail']['#description']);
    }
    // If the user viewing his account.
    if ($form_state['user']->uid == $user->uid) {
      $form['account']['current_pass']['#description'] = str_replace('Adresse de courriel', 'E-mail', $form['account']['current_pass']['#description']);
      $form['account']['mail']['#title'] = 'E-mail';
    }
    // If provider viewing his/her account.
    if (in_array('provider', array_values($user->roles)) && $form_state['user']->uid == $user->uid) {
      $form['account']['mail']['#description'] = str_replace('courriels', 'emails', $form['account']['mail']['#description']);
    }

    // Honorific, Name and Surname on the same line.
    $lang = $form['field_honorific']['#language'];
    $label = '<label for="edit-field-honorific-'. $lang .'">'. t('Last name') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
    $form['field_honorific']['#prefix'] = '<div class="container-wrapper">'. $label;
    $form['field_honorific'][$lang]['#options'] = array('_none' => t('Civilité')) + $form['field_honorific'][$lang]['#options'];
    $form['field_honorific'][$lang]['#title'] = t('Civilité');
    $form['field_honorific'][$lang]['#title_display'] = 'invisible';
    $form['field_honorific'][$lang]['#attributes']['class'][] = 'dropdown';
    $form['field_honorific']['#attributes']['class'][] = 'dropdown-honorific';

    $lang = $form['field_prenom']['#language'];
    $form['field_prenom'][$lang][0]['value']['#attributes']['placeholder'] = 
    $form['field_prenom'][$lang][0]['value']['#title'] = t('First name');
    $form['field_prenom'][$lang][0]['value']['#title_display'] = 'invisible';

    $lang = $form['field_name']['#language'];
    $form['field_name'][$lang][0]['value']['#attributes']['placeholder'] = 
    $form['field_name'][$lang][0]['value']['#title'] = t('Last name');
    $form['field_name'][$lang][0]['value']['#title_display'] = 'invisible';
    $form['field_name']['#suffix'] = '</div>';
    
    // Desired budgets.
    if (isset($form['profile_budget_profile']['field_number_budgets'])) {
      $lang = $form['profile_budget_profile']['field_number_budgets']['#language'];
      $form['profile_budget_profile']['field_number_budgets'][$lang]['#attributes']['class'][] = 'dropdown';
      $form['profile_budget_profile']['field_number_budgets']['#attributes']['class'][] = 'dropdown-regular';
    }

    // Website.
    $form['#after_build'][] = 'devis_form_user_profile_form_after_build';
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
  if (isset($form['profile_budget_profile']['field_active_regions_belgium'])) {
    $belgium = FALSE;
    $count = 0;
    // Check if Belgium is selected among the choices.
    $lang = $form['profile_budget_profile']['field_active_regions_belgium']['#language'];
    foreach ($form_state['values']['profile_budget_profile']['field_active_regions_belgium'][$lang] as $k => $val) {
      $count++;
      if ($val['value'] == 'BEL') $belgium = TRUE;
    }
    // If Belgium is selected, then asign only Belgium as the value.
    if ($belgium && $count > 1) {
      $new_value = array($lang => array(0 => array('value' => 'BEL')));
      $value['#parents'] = array('profile_budget_profile', 'field_active_regions_belgium'); 
      form_set_value($value, $new_value, $form_state);
    }
  }
}

function devis_user_profile_form_submit($form, &$form_state) {
  $user = $form_state['user'];
  $form_state['redirect'] = 'user/'. $user->uid;
  
  // Send the user to the first step.
  $profile = profile2_load_by_user($user);
  if (!$profile) {
    $form_state['redirect'] = 'user/'. $user->uid .'/edit-profile';
  }
  
  // Send the user to the second step.
  $billing_set = trois_devis_user_has_address_set($user->uid, 'billing');
  if ($profile && !$billing_set) {
    $billing_id = commerce_addressbook_get_default_profile_id($user->uid, 'billing');
    $form_state['redirect'] = 'user/'. $user->uid .'/addressbook/billing/edit/'. $billing_id;
  }
}

function devis_form_user_profile_form_after_build($form, &$form_state) {
  $form['field_website'][$form['field_website']['#language']][0]['url']['#attributes']['placeholder'] = 'www.siteweb.com';
  return $form;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_stripe_cardonfile_create_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  $orders = commerce_order_load_multiple(array(), array('uid' => $user->uid, 'status' => 'checkout_checkout'));
  $stored_cards = commerce_cardonfile_load_multiple_by_uid($user->uid);
  if ($orders || !$stored_cards) {
    $form['credit_card']['cardonfile_instance_default']['#default_value'] = 1;
    $form['credit_card']['cardonfile_instance_default']['#access'] = FALSE;
  }
  if ($orders) {
    drupal_set_message(t(variable_get('trois_devis_third_step_message')), 'warning');
  }
  
  drupal_set_title(t('Add credit card'));
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  $form['#attached']['css'][] = $theme_path .'/css/easydropdown.css';
  
  $form['errors']['#weight'] = -10;
  $form['card-info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Information'),
    '#weight' => 0,
  );
  // Change labels.
  $form['credit_card']['owner']['#title'] = t('Credit card owner');
  $form['credit_card']['number']['#title'] = t('Credit card number');
    
  $form['credit_card']['exp_month']['#title'] = t('Expiration date');
  $form['credit_card']['exp_month']['#title_display'] = 'invisible';
  $form['credit_card']['exp_month']['#prefix'] .= '<label for="edit-credit-card-exp-month">'. t('Expiration date') .'</label>';
  $form['credit_card']['exp_month']['#prefix'] .= '<div class="dropdown-expiration-date">';
  $form['credit_card']['exp_month']['#suffix'] = '</div>'. $form['credit_card']['exp_month']['#suffix'];
  $form['credit_card']['exp_month']['#attributes']['class'][] = 'dropdown';
  
  $form['credit_card']['exp_year']['#prefix'] = '<div class="dropdown-expiration-date">';
  $form['credit_card']['exp_year']['#suffix'] = '</div>'. $form['credit_card']['exp_year']['#suffix'];
  $form['credit_card']['exp_year']['#attributes']['class'][] = 'dropdown';
  
  $form['submit']['#value'] = t('Validate');
  $form['submit']['#attributes']['class'] = array('card_submit');
  $form['submit']['#suffix'] = l(t('Cancel'), 'user/'. $user->uid .'/cards', array('attributes' => array('class' => array('cancel_url'))));
  $form['submit']['#weight'] = 10;
  $form['credit_card']['cardonfile_instance_default']['#title'] = t('Set as your default credit card');
  //$form['address']['country']['#access'] = FALSE;
  //$form['address']['country']['#weight'] = 100;
  $form['address']['country']['#attributes']['class'][] = 'dropdown';
  $form['address']['street_block']['thoroughfare']['#title'] = t('Address');
  $form['address']['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['address']['street_block']['premise']['#title_display'] = 'invisible';
  
  $form['card-info']['credit_card'] = $form['credit_card'];
  $form['card-info']['address'] = $form['address'];
  
  unset($form['credit_card'], $form['address']);
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_cardonfile_card_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  $form['#attached']['css'][] = $theme_path .'/css/easydropdown.css';
  
  $form['errors']['#weight'] = -10;
  $form['card-info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Information'),
    '#weight' => 0,
  );
  $form['credit_card']['owner']['#title'] = t('Credit card owner');
  
  $label = '<label for="edit-credit-card-exp-month">'. t('Expiration date') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['credit_card']['exp_month']['#title'] = t('Expiration date');
  $form['credit_card']['exp_month']['#title_display'] = 'invisible';
  $form['credit_card']['exp_month']['#prefix'] .= $label;
  $form['credit_card']['exp_month']['#prefix'] .= '<div class="dropdown-expiration-date">';
  $form['credit_card']['exp_month']['#suffix'] = '</div>'. $form['credit_card']['exp_month']['#suffix'];
  $form['credit_card']['exp_month']['#attributes']['class'][] = 'dropdown';
  
  $form['credit_card']['exp_year']['#prefix'] = '<div class="dropdown-expiration-date">';
  $form['credit_card']['exp_year']['#suffix'] = '</div>'. $form['credit_card']['exp_year']['#suffix'];
  $form['credit_card']['exp_year']['#attributes']['class'][] = 'dropdown';
  
  $form['credit_card']['cardonfile_instance_default']['#title'] = t('Set as your default card');
  $form['submit']['#value'] = t('Update');
  $form['submit']['#weight'] = 10;
  $form['submit']['#attributes']['class'] = array('card_submit');
  $form['submit']['#suffix'] = l(t('Cancel'), 'user/'. $user->uid .'/cards', array('attributes' => array('class' => array('cancel_url'))));
  
  $form['card-info']['credit_card'] = $form['credit_card'];
  unset($form['credit_card']);
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_contact_site_form_alter(&$form, &$form_state, $form_id) {
  $form['contact'] = array(
    '#type' => 'fieldset',
    '#weight' => -10,
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['mail']['#title'] = 'E-mail';
  $pass = array('name', 'mail', 'subject', 'cid', 'message', 'copy');
  foreach ($pass as $key) {
    $form['contact'][$key] = $form[$key];
    unset($form[$key]);
  }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_legal_login_alter(&$form, &$form_state, $form_id) {
  // Get the node with the terms.
  $nid = variable_get('trois_devis_terms_nid', 4);
  $query = new EntityFieldQuery();
  $entities = $query->entityCondition('entity_type', 'node')
  ->propertyCondition('nid', $nid)
  ->propertyCondition('status', 1)
  ->execute();
  if (!empty($entities['node'])) {
    $node = node_load($nid);
    $temp = array_keys($node->body);
    $lang = array_shift($temp);
    $terms = $node->body[$lang][0]['value'];
    $form['legal']['info'] = array(
      '#markup' => $terms,
      '#weight' => -10,
    );
  }
  
  //$theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  //$form['#attached']['css'][] = $theme_path .'/css/dialog.css';
  //$form['#attached']['js'][] = $theme_path .'/js/jquery-ui/jquery-ui.js';
  $form['#validate'][] = 'devis_form_alter_validate';
  $form['#submit'][] = 'devis_form_legal_login_submit';
}

function devis_form_legal_login_submit($form, &$form_state) {
  $form_state['redirect'] = 'user';
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_addressbook_customer_profile_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  $billing_set = trois_devis_user_has_address_set($user->uid, 'billing');
  if (!$billing_set) {
    drupal_set_message(t(variable_get('trois_devis_second_step_message')), 'warning');
    $form_state['first_time'] = TRUE;
    $form_state['user'] = $user;
  }
  
  drupal_set_title(t('Coordonnées de facturation'));
  $lang = $form['commerce_customer_address']['#language'];
  $form['commerce_customer_address'][$lang][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['commerce_customer_address'][$lang][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['commerce_customer_address'][$lang][0]['street_block']['premise']['#title_display'] = 'invisible';
  
  //$form['field_tva']['#weight'] = 999;
  //$form['commerce_customer_address']['#type'] = 'fieldset';
  //$form['commerce_customer_address'][$lang][0]['field_tva'] = $form['field_tva'];
  //$form['field_tva']['#access'] = FALSE;
  
  // If the country is only one, then show the label of it.
  $country = $form['commerce_customer_address'][$lang][0]['country'];
  if (!$country['#access'] && count($country['#options']) === 1) {
    $temp = array_keys($country['#options']);
    $option = array_shift($temp);
    $form['commerce_customer_address'][$lang][0]['country_info'] = array(
      '#type' => 'item',
      '#title' => $country['#title'],
      '#markup' => $country['#options'][$option],
      '#weight' => 999,
    );
  }
  else {
    $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
    $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
    $form['#attached']['css'][] = $theme_path .'/css/easydropdown.css';
    $form['commerce_customer_address'][$lang][0]['country']['#weight'] = 999;
    $form['commerce_customer_address'][$lang][0]['country']['#attributes']['class'][] = 'dropdown';
  }
  
  $form['actions']['submit']['#value'] = t('Save');
  $form['#submit'][] = 'devis_form_commerce_addressbook_customer_profile_form_submit';
}

function devis_form_commerce_addressbook_customer_profile_form_submit($form, &$form_state) {
  if (isset($form_state['first_time'])) {
    $form_state['redirect'] = 'user/'. $form_state['user']->uid .'/cards/add';
  }
}

function devis_menu_local_tasks_alter(&$data, $router_item, $root_path) {
  global $user;
  
  $remove_tabs = FALSE;
  if (in_array('provider', $user->roles)) {
    // If coming to set the password for the first time,
    // then change some fields in the layout.
    $path = current_path();
    $profile = profile2_load_by_user($user);
    if (strpos($_SERVER['QUERY_STRING'], 'pass-reset-token') !== false && $path == 'user/'. $user->uid .'/edit' && !$profile) {
      $remove_tabs = TRUE;
    }
    // Second step, remove tabs.
    if (!$profile && $path == 'user/'. $user->uid .'/edit-profile') {
      $remove_tabs = TRUE;
    }
  }
  
  // Changing Address Book title.
  if (isset($data['tabs'][0]['output'])) {
    foreach ($data['tabs'][0]['output'] as $key => $info) {
      if ($info['#link']['title'] == 'Address Book') {
        $data['tabs'][0]['output'][$key]['#link']['title'] = t('Coordonnées de facturation');
      }
    }
  }
  if ($remove_tabs) {
    $data['tabs'][0]['count'] = 0;
  }
}

function devis_page_alter(&$page) {
  global $user;
  $path = current_path();
  $args = arg();
  // If admin viewing user pages, set a message to make him understand.
  if (in_array('manager', $user->roles) && isset($args[0]) && $args[0] == 'user' && isset($args[1]) && $args[1] != $user->uid) {
    drupal_set_message(t('Viewing as admin'), 'admin');
  }
  if (in_array('provider', $user->roles)) {
    $billing_id = commerce_addressbook_get_default_profile_id($user->uid, 'billing');
    $link = 'user/'. $user->uid .'/addressbook/billing/delete/'. $billing_id;
    if (isset($args[5]) && $args[5] == $billing_id && $path == $link) {
      watchdog('devis', t('Malicious user trying to delete address. User has been redirected.'), array(), WATCHDOG_ALERT, $link);
      drupal_goto('user/'. $user->uid .'/addressbook/billing/edit/'. $billing_id);
    }
  }
  
  // Delete message after setting password which gives the user the same message again.
  // Set the first step message on the account edit.
  if (strpos($_SERVER['QUERY_STRING'], 'pass-reset-token') === false) {
    // First stage message.
    if (in_array('provider', $user->roles)) {
      if ($path == 'user/'. $user->uid .'/edit-profile' || $path == 'user/'. $user->uid) {
        $messages = drupal_get_messages('status');
        if (isset($messages['status'])) {
          foreach ($messages['status'] as $msg) {
            if (strpos($msg, "Vous venez d'utiliser votre lien de connexion unique.") === false) {
              drupal_set_message($msg, 'status');
            }
          }
        }
      }
      
      $profile = profile2_load_by_user($user);
      if (!$profile && $path == 'user/'. $user->uid .'/edit-profile') {
        drupal_set_message(t(variable_get('trois_devis_first_step_message')), 'warning');
      }
    }
  }
}

function devis_profile2_view_alter($build) {
  /*if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    $build['#post_render'][] = 'my_module_post_render';
  }*/
}

//--------------- THEMES FROM MODULES ----------------//

/**
 * Implements theme_legal_accept_label().
 */
function devis_legal_accept_label($variables) {
  if ($variables['link']) {
    //$url = l(t('termes et conditions'), 'legal', array('attributes' => array('id' => 'opener', 'onclick' => 'return false')));
    //return t('<strong>Vous acceptez</strong> nos ') . $url;
    return t('<strong>Vous acceptez</strong> nos <a href="@terms" target="_blank">termes et conditions</a>', array('@terms' => url('legal')));
  }
  else {
    return t('<strong>Vous acceptez</strong> nos termes et conditions');
  }
}

/**
 * Implements theme_legal_login().
 */
function devis_legal_login($variables) {
  $user = user_load($variables['form']['uid']['#value']);
  $temp = array_keys($user->field_honorific);
  $lang = array_shift($temp);
  $honorific = $user->field_honorific[$lang][0]['value'];
  $welcome = ($honorific == 'female') ? 'Bienvenue' : 'Bienvenu'; // Bienvenu/e
  
  $form = $variables['form'];
  $form['legal']['#title'] = t('@welcome à 3devis.be', array('@welcome' => $welcome)); //t('Termes et conditions');
  $form = theme('legal_display', array('form' => $form));

  $output = '<p>' . t('Pour continuer à utiliser ce site s\'il vous plaît lire les Terms et conditions ci-dessous et confirmer votre acceptation.') . '</p>';

  if (isset($form['changes'])) {
    $form['changes']['#title'] = t('Changements');
    $form['changes']['#description'] = t('Les modifications apportées aux terms et conditions');
    $form['changes']['#collapsed'] = FALSE;
    $form['changes']['#collapsible'] = FALSE;
  }
  
  if (isset($form['changes']['#value'])) {
    foreach (element_children($form['changes']) as $key) {
      $form['changes'][$key]['#prefix'] .= '<li>';
      $form['changes'][$key]['#suffix'] .= '</li>';
    }

    $form['changes']['start_list'] = array('#value' => '<ul>', '#weight' => 0);
    $form['changes']['end_list']   = array('#value' => '</ul>', '#weight' => 3);
    $output .= drupal_render($form['changes']);
  }

  $save = drupal_render($form['save']);
  $output .= drupal_render_children($form);
  
  /*global $language;
  $conditions = legal_get_conditions($language->language);
  $conditions = $conditions['conditions'];
  $output .= '<div id="dialog" title="'. t('Termes et conditions') .'">'. $conditions .'</div>';*/
  
  $output .= $save;

  return $output;
}

function devis_menu_alter(&$items) {
}

function devis_mail_alter(&$message) {   
}

?>