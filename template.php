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
  //dpm($variables, 'variables');
  // good info: http://stackoverflow.com/questions/2383865/how-do-i-use-theme-preprocessor-functions-for-my-own-templates
  // Entityform budget.
  // Change the markup of the submitted information as it is not necessary.
  if ($entity_type == 'entityform' && $variables['elements']['#entity_type'] == 'entityform' && $variables['elements']['#bundle'] == 'comptable') {
    $variables['content']['info']['user']['#markup'] = ''; //Submitted by Anonyme on jeu, 06/12/2014 - 14:18
  }
  
  // Commerce Order view.
  if ($entity_type == 'commerce_order' && $variables['elements']['#entity_type'] == 'commerce_order') {
    // If it's not admin, do not show billing cycle.
    if (!$variables['is_admin']) {
      $variables['content']['cl_billing_cycle']['#access'] = FALSE;
    }
    $user_path = 'user/'. $variables['user']->uid;
    $variables['orders_url'] = l(t('Orders'), $user_path .'/orders');
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
}

function devis_preprocess(&$variables, $hook) {
  //dpm($variables, 'variables');
  //dpm($hook, 'hook');
  if ($hook == 'views_view_table') {
    //dpm($variables, 'variables');
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
}

/**
 * Implements theme_preprocess_views_view_table().
 */
function devis_preprocess_views_view_table(&$variables) {
  switch ($variables['view']->name) {
    case 'commerce_user_orders':
      foreach ($variables['rows'] as $i => $row) {
        switch ($row['status']) {
          case '':
            $variables['rows'][$i]['status'] = t('Change me');
            break;
        }
      }
      break;
  }
}

/**
 * Implements theme_legal_accept_label().
 */
function devis_legal_accept_label($variables) {
  if ($variables['link']) {
    return t('SE PUEDE CAMBIAR! <strong>Accept</strong> <a href="@terms">Terms & Conditions</a> of Use', array('@terms' => url('legal')));
  }
  else {
    return t('<strong>Accept</strong> Terms & Conditions of Use');
  }
}

function devis_entity_view_alter(&$build, $type) {
  // good info: http://drupal.stackexchange.com/questions/40307/how-to-customize-commerce-order-layout
  //dpm($build, 'build');
  //dpm($type, 'type');
  switch ($type) {
    case 'user':
      global $user;
    
      $build['field_honorific']['#access'] = FALSE;
      $build['field_prenom']['#access'] = FALSE;
      $string = $build['field_honorific'][0]['#markup'] .' '. $build['field_prenom'][0]['#markup'];
      $build['field_name'][0]['#markup'] = $string .' '. $build['field_name'][0]['#markup'];
    
      if (in_array('provider', array_values($user->roles))) {
        if (!$build['field_account_activity_status']['#items'][0]['value']) {
          $build['field_account_activity_status'][0]['#markup'] = '<span class="inactive">'. $build['field_account_activity_status'][0]['#markup'] .'</span>';
        }
      }
      break;
    
    case 'profile2':
      $count = $build['field_contacted_this_month']['#items'][0]['value'];
      $build['field_contacted_this_month'][0]['#markup'] = format_plural($count, '1 time', '@count times');
      $count = $build['field_contacted_total']['#items'][0]['value'];
      $build['field_contacted_total'][0]['#markup'] = format_plural($count, '1 time', '@count times');
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
          $children = str_replace(array('Titre', 'Order total'), array(t('Product'), t('Order total')), $children);
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
  /*
  // Remember to clear the CACHE before playing with forms.
  dpm($form, 'form');
  dpm($form_state, 'form_state');
  dpm($form_id, 'form_id');
  */
  switch ($form_id) {
    case 'commerce_cardonfile_card_form':
      $form['#validate'][] = 'devis_form_alter_validate';
      //$form['actions']['submit']['#validate'][] = 'devis_form_alter_validate';
      break;
  }
}

function devis_form_alter_validate(&$form_state, $form) {
  $errors = &$_SESSION['messages']['error'];
  //dpm($form, 'form');
  //dpm($form_state, 'form_state');
  //dpm($errors, 'errors');
  foreach ($errors as $item => $message) {
    switch ($message) {
      case 'You have specified an expired credit card.':
        $errors[$item] = t('Vous avez spécifié une carte de crédit qui a expiré.');
        break;
    }
  }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_comptable_entityform_edit_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  if (in_array('provider', $user->roles)) {
    drupal_set_message(t('Notice: You cannot make a budget request as you are a provider.'), 'warning');
    $form['actions']['submit']['#access'] = FALSE;
  }
  
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
  // Honorific, Name and Surname on the same line.
  $label = '<label for="edit-field-honorific-und">'. t('Last name') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['field_honorific']['#prefix'] = '<div class="container-wrapper">'. $label;
  $form['field_honorific']['und']['#options'] = array('_none' => t('Civilité')) + $form['field_honorific']['und']['#options'];
  $form['field_honorific']['und']['#title'] = t('Civilité');
  $form['field_honorific']['und']['#title_display'] = 'invisible';
  $form['field_honorific']['und']['#attributes']['class'][] = 'dropdown';
  $form['field_honorific']['#attributes']['class'][] = 'dropdown-honorific';
  
  $form['field_prenom']['und'][0]['value']['#attributes']['placeholder'] = 
  $form['field_prenom']['und'][0]['value']['#title'] = t('First name');
  $form['field_prenom']['und'][0]['value']['#title_display'] = 'invisible';

  $form['field_name']['und'][0]['value']['#attributes']['placeholder'] = 
  $form['field_name']['und'][0]['value']['#title'] = t('Last name');
  $form['field_name']['und'][0]['value']['#title_display'] = 'invisible';
  $form['field_name']['#suffix'] = '</div>';
  
  /*$form['field_prenom']['#prefix'] = '<div class="container-wrapper">';
  $form['field_prenom']['und'][0]['value']['#title'] = t('Last name');
  $form['field_prenom']['und'][0]['value']['#attributes']['placeholder'] = t('First name');

  $form['field_name']['und'][0]['value']['#attributes']['placeholder'] = t('Last name');
  $form['field_name']['und'][0]['value']['#title_display'] = 'invisible';
  $form['field_name']['#suffix'] = '</div>';*/
  
  // Email and telephone in the same line.
  $label = '<label for="edit-field-email">'. t('Contact') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['field_email']['#prefix'] = '<div class="container-wrapper">'. $label;
  $form['field_email']['und'][0]['email']['#title'] = 'E-mail';
  $form['field_email']['und'][0]['email']['#title_display'] = 'invisible';
  $form['field_email']['und'][0]['email']['#attributes']['placeholder'] = 'E-mail';
  $form['field_email']['und'][0]['email']['#attributes']['class'][] = 'email-input-class';

  $form['field_phone_belgium']['und'][0]['value']['#title_display'] = 'invisible';
  $form['field_phone_belgium']['und'][0]['value']['#attributes']['placeholder'] = t('Telephone');
  $form['field_phone_belgium']['#suffix'] = '</div>';
  
  // Website.
  $form['#after_build'][] = 'devis_form_comptable_entityform_edit_form_after_build';

  // Address fields.
  $form['field_adresse']['und'][0]['#prefix'] = '';
  $form['field_adresse']['und'][0]['#suffix'] = '';
  $form['field_adresse']['und'][0]['#title'] = '';
  $form['field_adresse']['und'][0]['#attributes']['class'][] = 'fieldset-hide';
  $form['field_adresse']['und'][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['field_adresse']['und'][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['field_adresse']['und'][0]['street_block']['premise']['#title_display'] = 'invisible';
  
  // Extra validation rules.
  $form['#validate'][] = 'devis_comptable_entityform_edit_form_validate';
  $form['actions']['submit']['#validate'][] = 'devis_comptable_entityform_edit_form_validate';
}

function devis_comptable_entityform_edit_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  // Postal code validation.
  if (module_exists('postal_code_validation')) {
    $address = $values['field_adresse']['und'][0];
    $postal_code = $address['postal_code'];
    $country = $address['country'];
    $result = postal_code_validation_validate($postal_code, $country);
    if ($result['error']) {
      form_set_error('field_adresse][und][0][postal_code', $result['error']);
    }
  }
  
  $value = $values['field_estimated_annual_revenue']['und'][0]['value'];
  if (isset($value)) $value = trim($value);
  if ($values['field_annual_revenue']['und'][0]['value'] == 'nonexistent' && !$value) {
    $label = $form['field_estimated_annual_revenue']['und']['#title'];
    form_set_error('field_estimated_annual_revenue][und][0][value', t('Le champ !label est requis.', array('!label' => $label)));
  }
  $value = $values['field_estimated_annual_invoice']['und'][0]['value'];
  if (isset($value)) $value = trim($value);
  if ($values['field_annual_invoice']['und'][0]['value'] == 'nonexistent' && !$value) {
    $label = $form['field_estimated_annual_invoice']['und']['#title'];
    form_set_error('field_estimated_annual_invoice][und][0][value', t('Le champ !label est requis.', array('!label' => $label)));
  }
  $value = $values['field_estimated_number_employees']['und'][0]['value'];
  if (isset($value)) $value = trim($value);
  if ($values['field_number_employees']['und'][0]['value'] == 'nonexistent' && !$value) {
    $label = $form['field_estimated_number_employees']['und']['#title'];
    form_set_error('field_estimated_number_employees][und][0][value', t('Le champ !label est requis.', array('!label' => $label)));
  }
  
  $company = $values['field_company_name']['und'][0]['value'];
  if (isset($company)) $company = trim($company);
  $tva = $values['field_tva']['und'][0]['value'];
  if (isset($tva)) $tva = trim($tva);
  $legal_status = $values['field_legal_status']['und'][0]['value'];
  $company_error = $tva_error = FALSE;
  $company_label = $form['field_company_name']['und']['#title'];
  $tva_label = $form['field_tva']['und']['#title'];
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
    form_set_error('field_company_name][und][0][value', t('Le champ !label est requis.', array('!label' => $company_label)));
  }
  if ($tva_error) {
    form_set_error('field_tva][und][0][value', t('Le champ !label est requis.', array('!label' => $tva_label)));
  }
  
  // TO FURTHER REPLACE ERROR MESSAGES, CHECK form_get_errors ON DRUPAL AND SEE THE COMMENTS!!!!!!!
}

function devis_form_comptable_entityform_edit_form_after_build($form, &$form_state) {
  $form['field_website']['und'][0]['url']['#attributes']['placeholder'] = 'www.siteweb.com';
  
  // JS variables for jQuery.
  $company = $form['field_company_name']['und'];
  $tva = $form['field_tva']['und'];
  $req_span = ' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span>';
  $devenir = array(
    'companyTitle' => $company['#title'],
    'tvaTitle' => $tva['#title'],
    'required' => $req_span,
  );
  drupal_add_js(array('devenir' => $devenir), 'setting');
  
  if ($form['field_legal_status']['und']['#value'] == 'association') {
    $form['field_company_name']['#title'] = 
      $form['field_company_name']['und']['#title'] = t('Nom de votre association');
    $form['field_tva']['#title'] = 
      $form['field_tva']['und']['#title'] = t('Numéro de TVA de votre association');
  }
  return $form;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_devenir_entityform_edit_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  if (in_array('provider', $user->roles)) {
    drupal_set_message(t('Notice: You are already a provider.'), 'warning');
    $form['actions']['submit']['#access'] = FALSE;
  }
  
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';

  // Honorific, Name and Surname on the same line.
  $label = '<label for="edit-field-honorific-und">'. t('Last name') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['field_honorific']['#prefix'] = '<div class="container-wrapper">'. $label;
  $form['field_honorific']['und']['#options'] = array('_none' => t('Civilité')) + $form['field_honorific']['und']['#options'];
  $form['field_honorific']['und']['#title'] = t('Civilité');
  $form['field_honorific']['und']['#title_display'] = 'invisible';
  $form['field_honorific']['und']['#attributes']['class'][] = 'dropdown';
  $form['field_honorific']['#attributes']['class'][] = 'dropdown-honorific';
  
  $form['field_prenom']['und'][0]['value']['#attributes']['placeholder'] = 
  $form['field_prenom']['und'][0]['value']['#title'] = t('First name');
  $form['field_prenom']['und'][0]['value']['#title_display'] = 'invisible';

  $form['field_name']['und'][0]['value']['#attributes']['placeholder'] = 
  $form['field_name']['und'][0]['value']['#title'] = t('Last name');
  $form['field_name']['und'][0]['value']['#title_display'] = 'invisible';
  $form['field_name']['#suffix'] = '</div>';
  
  // Website.
  $form['#after_build'][] = 'devis_form_devenir_entityform_edit_form_after_build';
  
  $desc = $form['field_info_extra']['und'][0]['value']['#description'];
  $form['field_info_extra']['und'][0]['value']['#attributes']['placeholder'] = $desc;//t('@description', array('@description' => $desc));
  $form['field_info_extra']['und'][0]['value']['#description'] = '';
  
  // If the request is being checked by a manager, so it is not new.
  if (in_array('manager', array_values($user->roles)) && !isset($form_state['build_info']['args'][0]->is_new)) {
    $entity = $form_state['build_info']['args'][0];
    $mail = $entity->field_email['und'][0]['email'];
    $approval = $entity->field_approval['und'][0]['value'];
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
  }

  // Extra validation rules.
  $form['#validate'][] = 'devis_devenir_entityform_edit_form_validate';
  $form['actions']['submit']['#validate'][] = 'devis_devenir_entityform_edit_form_validate';
  
  // Extra submission rules.
  //$form['#submit'][] = 'devis_devenir_entityform_edit_form_submit';
  //$form['actions']['save']['#submit'][] = 'devis_devenir_entityform_edit_form_submit';
}

/**
 * Validation for becoming accountant.
 */
function devis_devenir_entityform_edit_form_validate($form, &$form_state) {
  // Check if the email has been registered. Only for new forms.
  if (user_load_by_mail($form_state['values']['field_email']['und'][0]['email']) && isset($form['#entity']->is_new)) {
    $site_name = variable_get('site_name', '3devis.be');
    form_set_error('field_email][und][0][email', t('The specified email is already registered in !site_name.', array('!site_name' => $site_name)));
  }
}

function devis_form_devenir_entityform_edit_form_after_build($form, &$form_state) {
  $form['field_website']['und'][0]['url']['#attributes']['placeholder'] = 'www.siteweb.com';
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
  
  // Nicer select field.
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  
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
    }
    
    if ($form['#user_category'] == 'budget_profile') {
      $form['profile_budget_profile']['#access'] = FALSE;
      $form['actions']['submit']['#access'] = FALSE;
    }
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
    $label = '<label for="edit-field-honorific-und">'. t('Last name') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
    $form['field_honorific']['#prefix'] = '<div class="container-wrapper">'. $label;
    $form['field_honorific']['und']['#options'] = array('_none' => t('Civilité')) + $form['field_honorific']['und']['#options'];
    $form['field_honorific']['und']['#title'] = t('Civilité');
    $form['field_honorific']['und']['#title_display'] = 'invisible';
    $form['field_honorific']['und']['#attributes']['class'][] = 'dropdown';
    $form['field_honorific']['#attributes']['class'][] = 'dropdown-honorific';

    $form['field_prenom']['und'][0]['value']['#attributes']['placeholder'] = 
    $form['field_prenom']['und'][0]['value']['#title'] = t('First name');
    $form['field_prenom']['und'][0]['value']['#title_display'] = 'invisible';

    $form['field_name']['und'][0]['value']['#attributes']['placeholder'] = 
    $form['field_name']['und'][0]['value']['#title'] = t('Last name');
    $form['field_name']['und'][0]['value']['#title_display'] = 'invisible';
    $form['field_name']['#suffix'] = '</div>';

    // Website.
    $form['#after_build'][] = 'devis_form_user_profile_form_after_build';
  }
  
  // Address fields.
  /*$form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['street_block']['premise']['#title_display'] = 'invisible';
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['country']['#attributes']['style'] = 'display: none;';
  $form['field_customer_profile_adresse']['und']['profiles'][0]['commerce_customer_address']['und'][0]['country']['#title_display'] = 'invisible';*/
  
  if ($form['#user_category'] == 'budget_profile') {
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
  $form_state['redirect'] = 'user/'. $form_state['user']->uid;
}

function devis_form_user_profile_form_after_build($form, &$form_state) {
  $form['field_website']['und'][0]['url']['#attributes']['placeholder'] = 'www.siteweb.com';
  return $form;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_commerce_stripe_cardonfile_create_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  
  $form['errors']['#weight'] = -10;
  $form['card-info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Information'),
    '#weight' => 0,
  );
  $form['credit_card']['exp_month']['#title'] = t('Expiration date');
  $form['credit_card']['exp_month']['#title_display'] = 'invisible';
  $form['credit_card']['exp_month']['#prefix'] .= '<label for="edit-credit-card-exp-month">'. t('Expiration date') .'</label>';
  $form['credit_card']['exp_month']['#prefix'] .= '<div class="dropdown-expiration-date">';
  $form['credit_card']['exp_month']['#suffix'] = '</div>'. $form['credit_card']['exp_month']['#suffix'];
  $form['credit_card']['exp_month']['#attributes']['class'][] = 'dropdown';
  
  $form['credit_card']['exp_year']['#prefix'] = '<div class="dropdown-expiration-date">';
  $form['credit_card']['exp_year']['#suffix'] = '</div>'. $form['credit_card']['exp_year']['#suffix'];
  $form['credit_card']['exp_year']['#attributes']['class'][] = 'dropdown';
  
  $form['submit']['#attributes']['class'] = array('card_submit');
  $form['submit']['#suffix'] = l(t('Cancel'), 'user/'. $user->uid .'/cards', array('attributes' => array('class' => array('cancel_url'))));
  $form['submit']['#weight'] = 10;
  $form['credit_card']['cardonfile_instance_default']['#title'] = t('Set as your default card');
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
  
  $form['errors']['#weight'] = -10;
  $form['card-info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Information'),
    '#weight' => 0,
  );
  
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