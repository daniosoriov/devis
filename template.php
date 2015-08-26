<?php

// https://www.drupal.org/node/2093811 : Let users edit their customer profile outside of checkout

/**
 * @file template.php
 * Template overrides as well as (pre-)process and alter hooks for the
 * devis theme.
 */

/**
 * Implements hook_theme().
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
  return $items;
}

function devis_html_head_alter(&$head_elements) {
  $content = '';
  // Change the description head tag for the entityforms.
  $current_path = current_path();
  switch ($current_path) {
    case 'eform/submit/comptable':
      $content = variable_get('trois_devis_comptable_description', '');
      break;
    
    case 'eform/submit/devenir':
      $content = variable_get('trois_devis_devenir_description', '');
      break;
  }
  if ($content) {
    $head_elements['description'] = array(
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => array('name' => 'description', 'content' => $content),
    );
  }
}

/**
 * Implements theme_preprocess().
 */
function devis_preprocess(&$variables, $hook) {
  //dpm($variables, 'variables');
  //dpm($hook, 'hook');
  //dpm(array_keys($variables), 'variables KEYS');
  //dpm($variables['title'], 'title');
  //dpm($variables['head_title'], 'head_title');
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
  
  // Change head title for the entityforms.
  if ($hook == 'html') {
    $current_path = current_path();
    switch ($current_path) {
      case 'eform/submit/comptable':
        $variables['head_title'] = variable_get('trois_devis_comptable_title', '');
        break;
      
      case 'eform/submit/devenir':
        $variables['head_title'] = variable_get('trois_devis_devenir_title', '');
        break;
    }
  }
  
  // Add a global variable to jQuery.
  $devenir = array('url' => url('eform/submit/devenir'));
  drupal_add_js(array('devenir' => $devenir), 'setting');
}

function devis_entity_email_template_form($form, &$form_state, $entityform) {
  $form = array();
  $template = trois_devis_create_entityform_template($entityform, NULL, TRUE);
  $form['template'] = array(
    '#type' => 'text_format',
    '#format' => 'full_html_advanced',
    '#default_value' => $template,
  );
  return $form;
}

function devis_entity_view_alter(&$build, $type) {
  // good info: http://drupal.stackexchange.com/questions/40307/how-to-customize-commerce-order-layout
  //dpm($build, 'build');
  //dpm($type, 'type');
  
  switch ($type) {
    case 'entityform':
      $build['field_email_template_devis'][0]['#markup'] = render(drupal_get_form('devis_entity_email_template_form', $build['#entity']));
      break;
      
    // This is also on the preprocess but it is not working there, so I had to duplicate it here.
    case 'profile2':
      if (isset($build['field_contacted_this_month'])) {
        $count = $build['field_contacted_this_month']['#items'][0]['value'];
        $build['field_contacted_this_month'][0]['#markup'] = ($count) ? format_plural($count, '1 time', '@count times') : t('0 times');
      }
      if (isset($build['field_contacted_total'])) {
        $count = $build['field_contacted_total']['#items'][0]['value'];
        $build['field_contacted_total'][0]['#markup'] = ($count) ? format_plural($count, '1 time', '@count times') : t('0 times');
      }

      // Change the list of budgets layout for the viewed user.
      if (isset($build['field_entity_devis_ref'])) {
        $info_list = trois_devis_user_get_hash_list($build['#entity']->uid);
        foreach ($build['field_entity_devis_ref']['#items'] as $key => $arr) {
          if (!$arr['access']) continue;
          $entity = $arr['entity'];
          $info = $info_list[$entity->entityform_id];

          $lang_prenom = key($entity->field_prenom);
          $lang_name = key($entity->field_name);
          $lang_company = key($entity->field_company_name);
          $company = (isset($entity->field_company_name[$lang_company])) ? $entity->field_company_name[$lang_company][0]['safe_value'] : '';

          $markup = l($entity->entityform_id, 'entityform/'. $entity->entityform_id) .' - '. 
            $entity->field_prenom[$lang_prenom][0]['safe_value'] .' '.
            $entity->field_name[$lang_name][0]['safe_value'] .' - '.
            (($company) ? $company .' - ' : '') .
            format_date($entity->created, 'medium', '', NULL, 'en') .' -- '.
            l('User URL', 'devis_info/'. $info->url_info, array('attributes' => array('target' => '_blank', 'title' => 'This is the secure URL for the user to see the budget...', 'onclick' => 'return false'))) .' - '.
            (($info->date) ? 'viewed on '. format_date($info->date, 'medium', '', NULL, 'en') : '<em>not yet viewed</em>');
          $build['field_entity_devis_ref'][$key]['#markup'] = $markup;
        }
      }
      break;
    
    case 'user':
      break;
    
    case 'commerce_order':
      switch ($build['#view_mode']) {
        case 'pdf':
          $markup = $build['commerce_line_items'][0]['#markup'];
          $build['commerce_line_items'][0]['#markup'] = str_replace(array('Titre'), array(t('Details')), $markup);
          // An idea to fix the problem of the table is to create the table again from here using theme_table.
          break;
        
        case 'customer':
          drupal_set_title(t('Facture !number', array('!number' => $build['#entity']->order_number)));
          // Line items was giving me problems as they were not being displayed. 
          // In order to fix this, I changed the query rewritting to FALSE (View > Advanced > Query settings) on the line item view.
          // This is suggested here: https://www.drupal.org/node/1541206#comment-9103957
        
          // Client information.
          $children = '<section class="client-info">';
          $children .= '<article class="client-address">';
          $children .= '<h3 class="field-label">'. t('Client') .'</h3>';
          $children .= $build['commerce_customer_billing'][0]['#markup'];
          $children .= '</article>';
          // Invoice date and period.
          if (isset($build['field_commerce_billy_i_date'])) {
            $children .= '<article class="invoice-date">';
            $children .= '<h3 class="field-label">'. t('Invoice date') .'</h3>';
            $info = $build['field_commerce_billy_i_date']['#items'][0];
            $date = strtolower(format_date($info['value'], 'custom', 'j F Y', $info['timezone']));
            $children .= '<div class="field-date">'. $date .'</div>';
            
            $children .= '<h3 class="field-label">'. t('Period') .'</h3>';
            $date = date('d F Y', $info['value']);
            $period = format_date(strtotime($date .' - 1 month'), 'custom', 'F Y', $info['timezone']);
            $children .= '<div class="field-date">'. $period .'</div>';
            $children .= '</article>';
            $build['field_commerce_billy_i_date']['#access'] = FALSE;
          }
          $children .= '</section>';
          $var = array('element' => array('#children' => $children, '#title' => ''));//t('Billing information')));
          $build['commerce_customer_billing']['#weight'] = -10;
          $build['commerce_customer_billing']['#title'] = '';
          $build['commerce_customer_billing'][0]['#markup'] = theme_fieldset($var);
        
          // Line items.
          // Put markups inside fieldets so it looks the same as the checkout order.
          $children = $build['commerce_line_items'][0]['#markup'];
          $children .= '<div class="space"></div>';
          $children .= $build['commerce_order_total'][0]['#markup'];
          $children = str_replace('Order total', t('Total'), $children);
          $var = array('element' => array('#children' => $children, '#title' => ''));//t('Products')));
          $build['commerce_line_items'][0]['#markup'] = theme_fieldset($var);
          $build['commerce_order_total'][0]['#markup'] = '';
        
          // Information about us.
          $children = '<hr /><p class="invoice-footer">';
          $children .= variable_get('trois_devis_billing_address', '');
          $children .= '</p>';
          $build['commerce_order_total'][0]['#markup'] = $children;
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
    case 'simplenews_confirm_removal_form':
      $form['description']['#access'] = FALSE;
      $form['#submit'][] = 'devis_form_alter_submit';
      break;
    
    case 'commerce_cardonfile_card_form':
      $form['#validate'][] = 'devis_form_alter_validate';
      //$form['actions']['submit']['#validate'][] = 'devis_form_alter_validate';
      break;
    
    case 'entityform_delete_form':
      $entityform = $form_state['entityform'];
      switch ($entityform->type) {
        case 'devenir':
          $title = 'Provider';
          $redirect = 'adminpage/request/provider';
          break;
        
        case 'comptable':
          $title = 'Budget';
          $redirect = 'adminpage/request/budget';
          break;
      }
      $form_state['redirect_url'] = $redirect;
      drupal_set_title($title .' request '. $entityform->entityform_id);
      $form['description']['#markup'] = '<p>Are you sure you want to delete this submission? This action cannot be undone.</p>';
      $form['#submit'][] = 'devis_form_alter_submit';
      $form['actions']['cancel']['#href'] = $redirect;
      break;
  }
}

function devis_form_alter_validate($form, &$form_state) {
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
    if ($form['#id'] == 'legal-login' || $form['#id'] == 'legal_login') {
      if (isset($errors[0]) && $errors[0] != '' && count($errors) == 1) {
        $errors[0] = t('Vous devez accepter nos conditions générales pour continuer.');
      }
    }
  }
}

function devis_form_alter_submit($form, &$form_state) {
  switch ($form['#id']) {
    case 'simplenews-confirm-removal-form':
      $status = $_SESSION['messages']['status'];
      if (isset($status[0]) && $status[0] != '' && count($status) == 1) {
        unset($_SESSION['messages']);
      }
      $nid = variable_get('trois_devis_unsubscribe_confirm_nid', 0);
      $form_state['redirect'] = 'node/'. $nid;
      break;
    
    case 'entityform-delete-form':
      $form_state['redirect'] = $form_state['redirect_url'];
      break;
  }
}

/**
 * Find the prices for a given legal status based on the products.
 */
function devis_get_prices_from_legal_status($legal_status) {
  $product_key = trois_devis_get_product_from_status($legal_status);
  $products = commerce_product_load_multiple(array(), array('type' => 'devis'));
  $search = array($product_key, $product_key .'_low', $product_key .'_high');
  $keep = $temp = $order = array();
  foreach ($products as $key => $product) {
    if (in_array($product->sku, $search)) {
      $price = commerce_product_calculate_sell_price($product);
      $price_display = commerce_currency_format($price['amount'], $price['currency_code'], $product);
      $temp[$key] = $price_display;
      $order[$key] = $price['amount'];
    }
  }
  asort($order, SORT_NUMERIC);
  foreach ($order as $key => $price) {
    $keep[$key] = $temp[$key];
  }
  return $keep;
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
  
  // If a manager/admin is viewing the form.
  $manager_view = (in_array('manager', $user->roles) && !isset($form_state['build_info']['args'][0]->is_new)) ? TRUE : FALSE;
  $form_state['manager_view'] = $manager_view;
  if ($manager_view) {
    $entity = $form_state['build_info']['args'][0];
    $lang = key($entity->field_approval);
    $approval = $entity->field_approval[$lang][0]['value'];
    // If the request has been already approved or denied.
    if ($approval != 'pending') {
      drupal_set_message(t('Notice: This request can no longer be modified.'), 'warning');
      $form['actions']['#access'] = FALSE;
    }
    else {
      drupal_set_message(t('Notice: Please check the sensitive information fields: Additional Information and Activity'), 'warning');
      
      $legal_status = ($form_state['values']) ? 
        $form_state['values']['field_legal_status'][$form['field_legal_status']['#language']][0]['value'] : 
        $form['field_legal_status'][$form['field_legal_status']['#language']]['#default_value'][0];
      
      $keep = devis_get_prices_from_legal_status($legal_status);
      unset($form['field_devis_product'][$form['field_devis_product']['#language']]['#options']);
      foreach ($keep as $key => $val) {
        $form['field_devis_product'][$form['field_devis_product']['#language']]['#options'][$key] = $val;
      }
      
      
      // Calculate potential providers to contact.
      /*$result = trois_devis_get_accountants_to_contact($entity);
      if (!$result) {
        drupal_set_message(t('Warning: This budget request cannot be approved yet. There are no providers to contact which match the specified parameters.'));
      }
      else {
        $string = array();
        foreach ($result as $tmp) {
          $account_tmp = user_load($tmp->uid);
          $variables = array(
            'account' => $account_tmp, 
            'name' => $account_tmp->realname, 
            'extra' => '',
            'link_path' => '/user/'. $account_tmp->uid,
            'link_options' => array('attributes' => array('target' => '_blank')),
          );
          $string[] = theme_username($variables);
        }
       drupal_set_message(t('Users that will be contacted: !users.', array('!users' => implode($string, ', '))), 'warning');
       
      }*/
    }
    $form_state['set_redirect'] = TRUE;
  }
  
  $theme_path = drupal_get_path('theme', variable_get('theme_default', NULL));
  $form['#attached']['js'][] = $theme_path .'/js/easydropdown/jquery.easydropdown.min.js';
  $form['#attached']['css'][] = $theme_path .'/css/easydropdown.css';
  
  $lang = $form['field_info_extra']['#language'];
  $desc = $form['field_info_extra'][$lang][0]['value']['#description'];
  $form['field_info_extra'][$lang][0]['value']['#attributes']['placeholder'] = $desc;//t('@description', array('@description' => $desc));
  $form['field_info_extra'][$lang][0]['value']['#description'] = '';
  
  $form['field_legal_status'][$form['field_legal_status']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_desired_benefits'][$form['field_desired_benefits']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_annual_revenue'][$form['field_annual_revenue']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_annual_invoice'][$form['field_annual_invoice']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_number_employees'][$form['field_number_employees']['#language']]['#attributes']['class'][] = 'dropdown';
  $form['field_change_accountant_reason'][$form['field_change_accountant_reason']['#language']]['#attributes']['class'][] = 'dropdown';
  
  $lang = $form['field_change_accountant_reason']['#language'];
  $form['field_change_accountant_reason'][$lang]['#options']['_none'] = t('- Select -');
  
  // Admin fields.
  $lang = $form['field_activity_admin']['#language'];
  $form['field_activity_admin'][$lang][0]['value']['#prefix'] = '<div class="admin-field">';
  $form['field_activity_admin'][$lang][0]['value']['#suffix'] = '</div>';
  
  $lang = $form['field_info_extra_admin']['#language'];
  $form['field_info_extra_admin'][$lang][0]['value']['#prefix'] = '<div class="admin-field">';
  $form['field_info_extra_admin'][$lang][0]['value']['#suffix'] = '</div>';
  
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
  $form['field_email'][$lang][0]['email']['#attributes']['class'][] = 'input-fixed-width';

  $lang = $form['field_phone_belgium']['#language'];
  $form['field_phone_belgium'][$lang][0]['value']['#title_display'] = 'invisible';
  //$form['field_phone_belgium'][$lang][0]['value']['#prefix'] = '<span class="prefix-phone">+32</span>';
  $form['field_phone_belgium'][$lang][0]['value']['#attributes']['placeholder'] = t('Telephone');
  $form['field_phone_belgium'][$lang][0]['value']['#attributes']['class'][] = 'input-fixed-width';
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
    $lang = key($values['field_adresse']);
    $postal_code = $values['field_adresse'][$lang][0]['postal_code'];
    $regions = trois_devis_get_region_by_postal_code($postal_code) .'+BEL';
    $result = views_get_view_result('provider', 'accountants_to_contact_new', $legal_status, $regions);
    
    if (empty($result)) {
      form_set_error('field_approval]['. $lang .'][0][value', t('This budget request cannot be approved yet. There are no providers to contact which match the specified parameters.'));
    }
  }

  // E-mail check.
  $lang = $form['field_email']['#language'];
  if (!valid_email_address($form_state['values']['field_email'][$lang][0]['email'])) {
    form_set_error('field_email]['. $lang .'][0][email', t('The specified email is not a valid email.'));
  }
  devis_field_phone_belgium_validate($form, $form_state);
  if ($legal_status == 'society' || $legal_status == 'independent') {
    devis_field_tva_validate($form, $form_state);
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

  // If it has accountant, make mandatory the dependee.
  $lang = $form['field_has_accountant']['#language'];
  if ($values['field_has_accountant'][$lang][0]['value']) {
    $lang = $form['field_change_accountant_reason']['#language'];
    $value = $values['field_change_accountant_reason'][$lang][0]['value'];
    if (!$value) {
      $label = $form['field_change_accountant_reason'][$lang]['#title'];
      form_set_error('field_change_accountant_reason]['. $lang .'][0][value', t('Le champ !label est requis.', array('!label' => $label)));
    }
    elseif ($value == 'other') {
      $lang = $form['field_change_accountant_other']['#language'];
      if (!trim($values['field_change_accountant_other'][$lang][0]['value'])) {
        $label = $form['field_change_accountant_other'][$lang]['#title'];
        form_set_error('field_change_accountant_other]['. $lang .'][0][value', t('Le champ !label est requis.', array('!label' => $label)));
      }
    }
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
      /* It seems TVA for association is not mandatory.
      if (!$tva) {
        $tva_label = 'Numéro de TVA de votre association';
        $tva_error = TRUE;
      }*/
      if ($tva) {
        devis_field_tva_validate($form, $form_state);
      }
      break;

    case 'society':
      if (!$company) $company_error = TRUE;
      if (!$tva) $tva_error = TRUE;
      break;

    case 'independent':
    case 'independent_comp':
      if (!$tva) $tva_error = TRUE;
      break;
  }
  if ($company_error) {
    form_set_error('field_company_name]['. $lang .'][0][value', t('Le champ !label est requis.', array('!label' => $company_label)));
  }
  if ($tva_error) {
    form_set_error('field_tva]['. $lang_tva .'][0][value', t('Le champ !label est requis.', array('!label' => $tva_label)));
  }

  // If it's new, update the admin fields with the normal field values.
  if (isset($form_state['build_info']['args'][0]->is_new)) {
    $lang = $form['field_activity_admin']['#language'];
    $lang_act = $form['field_activity']['#language'];
    $new_value = array($lang => array(0 => array('value' => $values['field_activity'][$lang_act][0]['value'])));
    $value = array('#parents' => array('field_activity_admin'));
    form_set_value($value, $new_value, $form_state);

    $lang = $form['field_info_extra_admin']['#language'];
    $lang_act = $form['field_info_extra']['#language'];
    $new_value = array($lang => array(0 => array('value' => $values['field_info_extra'][$lang_act][0]['value'])));
    $value = array('#parents' => array('field_info_extra_admin'));
    form_set_value($value, $new_value, $form_state);
  }

  // If the form was submitted and there are errors, 
  // scroll the browser to the messages box.
  // Add some variables to jQuery.
  $error = isset($_SESSION['messages']['error']) ? TRUE : FALSE;
  $data = array('submitted' => TRUE, 'error' => $error);
  drupal_add_js(array('form_info' => $data), 'setting');
}

function devis_comptable_entityform_edit_form_submit($form, &$form_state) {
  dpm($form_state, 'form_state SUBMIT');
  // Only for manager/admin.
  if (isset($form_state['stage'])) {
    $rebuild = TRUE;
    // If on initial stage, set the preview stage.
    if ($form_state['stage'] == 'initial') {
      $form_state['stage'] = 'preview';
      $form_state['multistep_values'][$form_state['stage']] = $form_state['values'];
    }
    // If in preview stage and going back.
    if ($form_state['stage'] == 'preview' && $form_state['triggering_element']['#id'] == 'edit-back') {
      $form_state['stage'] = 'initial';
    }
    elseif ($form_state['stage'] == 'preview' && $form_state['triggering_element']['#id'] == 'edit-next') {
      $rebuild = FALSE;
    }
    if (isset($form_state['multistep_values']['form_build_id'])) {
      $form_state['values']['form_build_id'] = $form_state['multistep_values']['form_build_id'];
    }
    if ($rebuild) {
      $form_state['multistep_values']['form_build_id'] = $form_state['values']['form_build_id'];
      $form_state['rebuild'] = TRUE;
    }
  }
  
  if (isset($form_state['set_redirect'])) {
    $form_state['redirect'] = 'adminpage/request/budget';
  }
}

function devis_form_comptable_entityform_edit_form_after_build($form, &$form_state) {
  $form['field_website'][$form['field_website']['#language']][0]['url']['#attributes']['placeholder'] = 'www.siteweb.com';
  
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
  
  $prices = array();
  foreach ($form['field_legal_status'][$form['field_legal_status']['#language']]['#options'] as $key => $val) {
    $options = devis_get_prices_from_legal_status($key);
    $prices[$key] = $options;
  }
  drupal_add_js(array('prices' => $prices), 'setting');
  
  if ($form['field_legal_status'][$form['field_legal_status']['#language']]['#value'] == 'association') {
    $form['field_company_name']['#title'] = 
      $form['field_company_name'][$lang]['#title'] = t('Nom de votre association');
    //$form['field_tva']['#title'] = 
    //  $form['field_tva'][$lang_tva]['#title'] = t('Numéro de TVA de votre association');
  }
  return $form;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_devenir_entityform_edit_form_alter(&$form, &$form_state, $form_id) {
  global $user;
  
  // Check if the user is coming from an email with an URL to the registration.
  /*$current_url = url(current_path(), array('absolute' => TRUE, 'query' => drupal_get_query_parameters()));
  $parse = drupal_parse_url($current_url);
  if (user_is_anonymous()) {
    $hash = $email = $promo_code = '';
    //user_cookie_delete('devis_e_hash');
    //user_cookie_delete('devis_p_c');
    
    // If the values are in the cookie, use them.
    if (isset($_COOKIE['Drupal_visitor_devis_e_hash'])) {
      $hash = $_COOKIE['Drupal_visitor_devis_e_hash'];
    }
    if (isset($_COOKIE['Drupal_visitor_devis_p_c'])) {
      $promo_code = $_COOKIE['Drupal_visitor_devis_p_c'];
    }
    
    // If the values are in the URL, save them in the cookies.
    // Since the values are not saved immediately, we have to keep them in
    // a local variable.
    $values = array();
    if (isset($parse['query']['id'])) {
      $values['devis_e_hash'] = $hash = $parse['query']['id'];
    }
    if (isset($parse['query']['code'])) {
      $values['devis_p_c'] = $promo_code = $parse['query']['code'];
    }
    // If there are values to save, then save in the cookie.
    if ($values) {
      user_cookie_save($values);
    }
    $form_state['hash'] = $hash;
    $form_state['hash_email'] = trois_devis_get_hash_email($hash);
    $form['field_hash_email'][$form['field_hash_email']['#language']][0]['email']['#default_value'] =
    $form['field_email'][$form['field_email']['#language']][0]['email']['#default_value'] = $form_state['hash_email'];
  }*/
  
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
  
  // Email and telephone in the same line.
  $lang = $form['field_email']['#language'];
  $label = '<label for="edit-field-email-'. $lang .'">'. t('Contact') .' <span class="form-required" title="'. t('Ce champ est requis.') .'">*</span></label>';
  $form['field_email']['#prefix'] = '<div class="container-wrapper">'. $label;
  $form['field_email'][$lang][0]['email']['#title'] = 'E-mail';
  $form['field_email'][$lang][0]['email']['#title_display'] = 'invisible';
  $form['field_email'][$lang][0]['email']['#attributes']['placeholder'] = 'E-mail';
  $form['field_email'][$lang][0]['email']['#attributes']['class'][] = 'input-fixed-width';
  
  $lang = $form['field_phone_belgium']['#language'];
  $form['field_phone_belgium'][$lang][0]['value']['#title_display'] = 'invisible';
  //$form['field_phone_belgium'][$lang][0]['value']['#prefix'] = '<span class="prefix-phone">+32</span>';
  $form['field_phone_belgium'][$lang][0]['value']['#attributes']['placeholder'] = t('Telephone');
  $form['field_phone_belgium'][$lang][0]['value']['#attributes']['class'][] = 'input-fixed-width';
  $form['field_phone_belgium']['#suffix'] = '</div>';
  
  // Website.
  $form['#after_build'][] = 'devis_form_devenir_entityform_edit_form_after_build';
  
  // Address fields.
  $lang = $form['field_adresse']['#language'];
  $form['field_adresse'][$lang][0]['#prefix'] = '';
  $form['field_adresse'][$lang][0]['#suffix'] = '';
  $form['field_adresse'][$lang][0]['#title'] = '';
  $form['field_adresse'][$lang][0]['#attributes']['class'][] = 'fieldset-hide';
  $form['field_adresse'][$lang][0]['street_block']['thoroughfare']['#title'] = t('Address');
  $form['field_adresse'][$lang][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
  $form['field_adresse'][$lang][0]['street_block']['premise']['#title_display'] = 'invisible';
  
  /*$lang = $form['field_info_extra']['#language'];
  $desc = $form['field_info_extra'][$lang][0]['value']['#description'];
  $form['field_info_extra'][$lang][0]['value']['#attributes']['placeholder'] = $desc;//t('@description', array('@description' => $desc));
  $form['field_info_extra'][$lang][0]['value']['#description'] = '';
  $form['field_info_extra']['#access'] = FALSE;*/
  
  // If the request is being checked by a manager, so it is not new.
  if (in_array('manager', array_values($user->roles)) && !isset($form_state['build_info']['args'][0]->is_new)) {
    $entity = $form_state['build_info']['args'][0];
    $mail = $entity->field_email[$form['field_email']['#language']][0]['email'];
    $lang = key($entity->field_approval);
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
  
  // E-mail check.
  if (!valid_email_address($form_state['values']['field_email'][$lang][0]['email'])) {
    form_set_error('field_email]['. $lang .'][0][email', t('The specified email is not a valid email.'));
  }
  devis_field_phone_belgium_validate($form, $form_state);
  devis_field_tva_validate($form, $form_state);
  
  // If the form was submitted and there are errors, 
  // scroll the browser to the messages box.
  // Add some variables to jQuery.
  $error = isset($_SESSION['messages']['error']) ? TRUE : FALSE;
  $data = array('submitted' => TRUE, 'error' => $error);
  drupal_add_js(array('form_info' => $data), 'setting');
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
      $form['field_phone_belgium']['#access'] = FALSE;
      $form['field_website']['#access'] = FALSE;
      $form['field_account_activity_status']['#access'] = FALSE;
      $form['field_customer_profile_adresse']['#access'] = FALSE;
      $form['field_promotional_code']['#access'] = FALSE;
      $form['field_promo_code_usage']['#access'] = FALSE;
      $form['field_number_ipcf_iec']['#access'] = FALSE;
      $form['field_staff_number']['#access'] = FALSE;
      $form['field_adresse']['#access'] = FALSE;
      $form['profile_budget_profile']['#access'] = FALSE;
      $form['actions']['cancel']['#access'] = FALSE;
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
    
    // Address fields.
    $lang = $form['field_adresse']['#language'];
    $form['field_adresse'][$lang][0]['#prefix'] = '';
    $form['field_adresse'][$lang][0]['#suffix'] = '';
    $form['field_adresse'][$lang][0]['#title'] = '';
    $form['field_adresse'][$lang][0]['#attributes']['class'][] = 'fieldset-hide';
    $form['field_adresse'][$lang][0]['street_block']['thoroughfare']['#title'] = t('Address');
    $form['field_adresse'][$lang][0]['street_block']['premise']['#attributes']['style'] = 'display: none;';
    $form['field_adresse'][$lang][0]['street_block']['premise']['#title_display'] = 'invisible';
    
    /*
    $lang = $form['field_phone_belgium']['#language'];
    $form['field_phone_belgium'][$lang][0]['value']['#field_prefix'] = '<span class="prefix-phone">+32</span>';
    $form['field_phone_belgium'][$lang][0]['#attributes']['class'][] = 'input-with-prefix';*/
    
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
  
  if (isset($form_state['values']['mail'])) {
    // E-mail check for duplicate user.
    $user_check = user_load_by_mail($form_state['values']['mail']);
    if ($user_check && $form['#user']->uid != $user_check->uid) {
      $site_name = variable_get('site_name', '3devis.be');
      form_set_error('mail', t('The specified email is already registered in !site_name.', array('!site_name' => $site_name)));
    }

    // E-mail check.
    if (!valid_email_address($form_state['values']['mail'])) {
      form_set_error('mail', t('The specified email is not a valid email.'));
    }
  }
  devis_field_phone_belgium_validate($form, $form_state);
  devis_field_tva_validate($form, $form_state);
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
  // If it's the first card to add.
  if ($orders || !$stored_cards) {
    $form['credit_card']['cardonfile_instance_default']['#default_value'] = 1;
    $form['credit_card']['cardonfile_instance_default']['#access'] = FALSE;
  }
  // If not, show the cancel button to go back.
  else {
    $form['submit']['#suffix'] = l(t('Cancel'), 'user/'. $user->uid .'/cards', array('attributes' => array('class' => array('cancel_url'))));
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
  $form['submit']['#weight'] = 10;
  $form['credit_card']['cardonfile_instance_default']['#title'] = t('Set as your default credit card');
  $form['address']['country']['#access'] = FALSE;
  $form['address']['country']['#weight'] = 100;
  // This is now here as is not working on commerce stripe.
  //$form['address']['country']['#attributes']['class'][] = 'dropdown';
  //$form['address']['street_block']['thoroughfare']['#title'] = t('Address');
  //$form['address']['street_block']['premise']['#attributes']['style'] = 'display: none;';
  //$form['address']['street_block']['premise']['#title_display'] = 'invisible';
  
  $form['card-info']['credit_card'] = $form['credit_card'];
  $form['card-info']['address'] = $form['address'];
  
  unset($form['credit_card'], $form['address']);
  // Pass the default card to the next step in case there is one.
  $form['#submit'][] = 'devis_form_commerce_stripe_cardonfile_create_form_submit';
  $stored_cards = commerce_cardonfile_load_multiple_by_uid($user->uid, NULL, TRUE);
  if ($stored_cards) {
    $default_card = current($stored_cards);
    $form_state['default_card'] = $default_card;
  }
}

function devis_form_commerce_stripe_cardonfile_create_form_submit($form, &$form_state) {
  // If the user didn't select the new card as a default card,
  // then correct the problem that makes this new card as the default one,
  // by asigning the default value back to the old default card.
  if (!$form_state['values']['credit_card']['cardonfile_instance_default']) {
    commerce_cardonfile_set_default_card($form_state['default_card']->card_id);
  }
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
  
  $form['#submit'][] = 'devis_form_contact_site_form_submit';
}

function devis_form_contact_site_form_submit($form, &$form_state) {
  drupal_set_message(t('Merci, nous vous contacterons bientôt.'));
  $form_state['redirect'] = 'contact';
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 */
function devis_form_legal_login_alter(&$form, &$form_state, $form_id) {
  // If there are some changes in the conditions, display the changes in
  // an open fieldset.
  if (isset($form['changes'])) {
    $form['changes']['collapsed'] = FALSE;
    $form['changes']['collapsible'] = FALSE;
  }
  // Otherwise, it's a first time login, so show the terms.
  else {
    $nid = variable_get('trois_devis_terms_nid', 4);
    $query = new EntityFieldQuery();
    $entities = $query->entityCondition('entity_type', 'node')
    ->propertyCondition('nid', $nid)
    ->propertyCondition('status', 1)
    ->execute();
    if (!empty($entities['node'])) {
      $node = node_load($nid);
      $lang = key($node->body);
      $terms = $node->body[$lang][0]['value'];
      $form['legal']['info'] = array(
        '#markup' => $terms,
        '#weight' => -10,
      );
    }
  }
  $form['#validate'][] = 'devis_form_alter_validate';
  $form['#submit'][] = 'devis_form_legal_login_submit';
}

/**
 * This function is copying the same code from legal.
 */
function devis_form_legal_login_submit($form, &$form_state) {
  // This is a copy from the legal module.
  global $user;
  $values = $form_state['values'];
  $user   = user_load($values['uid']);

  $redirect = 'user/' . $user->uid;

  if (!empty($_GET['destination'])) {
    $redirect = $_GET['destination'];
  }

  $form_state['redirect'] = $redirect;

  // Update the user table timestamp noting user has logged in.
  db_update('users')
    ->fields(array('login' => time()))
    ->condition('uid', $user->uid)
    ->execute();

  // User has new permissions, so we clear their menu cache.
  cache_clear_all($user->uid, 'cache_menu', TRUE);
  // Fixes login problems in Pressflow.
  drupal_session_regenerate();
  user_module_invoke('login', $edit, $user);
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
    $form['field_is_new'][$form['field_is_new']['#language']]['#default_value'] = 0;
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
    $option = key($country['#options']);
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
  if ($remove_tabs) {
    $data['tabs'][0]['count'] = 0;
  }
  
  // Changing Address Book title.
  if (isset($data['tabs'][0]['output'])) {
    foreach ($data['tabs'][0]['output'] as $key => $info) {
      if ($info['#link']['title'] == 'Address Book') {
        $data['tabs'][0]['output'][$key]['#link']['title'] = t('Coordonnées de facturation');
      }
      if ($info['#link']['path'] == 'adminpage/request/provider') {
        $data['tabs'][0]['output'][$key]['#link']['title'] = 'Providers';
      }
    }
  }
  
  // Removing unnecessary tabs for the admin.
  if (in_array('manager', $user->roles)) {
    $args = arg();
    // If admin is watching his account.
    if (isset($args[1]) && $args[1] == $user->uid) {
      $total = 0;
      if (isset($data['tabs'][0]['output'])) {
        foreach ($data['tabs'][0]['output'] as $key => $info) {
          if ($info['#link']['path'] == 'user/%/addressbook') {
            unset($data['tabs'][0]['output'][$key]);
            $total++;
          }
          if ($info['#link']['path'] == 'user/%/demandes') {
            unset($data['tabs'][0]['output'][$key]);
            $total++;
          }
          if ($info['#link']['path'] == 'user/%/orders') {
            unset($data['tabs'][0]['output'][$key]);
            $total++;
          }
          if ($info['#link']['path'] == 'user/%/cards') {
            unset($data['tabs'][0]['output'][$key]);
            $total++;
          }
        }
      }
      $data['tabs'][0]['count'] -= $total;
    }
  }
  
  // Remove devel tabs for everybody.
  if (isset($data['tabs'][0]['output'])) {
    foreach ($data['tabs'][0]['output'] as $key => $info) {
      if ($info['#link']['path'] == 'user/%/devel/token') {
        unset($data['tabs'][0]['output'][$key]);
        $data['tabs'][0]['count'] -= 1;
      }
    }
  }
  
  // Remove newsletter tabs for everybody.
  if (isset($data['tabs'][1]['output'])) {
    foreach ($data['tabs'][1]['output'] as $key => $info) {
      if ($info['#link']['path'] == 'user/%/edit/simplenews') {
        unset($data['tabs'][1]['output'][$key]);
        $data['tabs'][1]['count'] -= 1;
      }
    }
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
    return t('<strong>Vous acceptez</strong> nos <a href="@terms" target="_blank">conditions générales</a>', array('@terms' => url('legal')));
  }
  else {
    return t('<strong>Vous acceptez</strong> nos conditions générales');
  }
}

/**
 * Implements theme_legal_login().
 */
function devis_legal_login($variables) {
  $output = '';
  $form = $variables['form'];
  $form['legal']['#title'] = '';
  $form = theme('legal_display', array('form' => $form));

  // If the changes exist, display them in a new fieldset and delete the
  // module layout for the changes.
  if (isset($form['changes'])) {
    $form['changes_new'] = array(
      '#type'        => 'fieldset',
      '#title'       => t('Les modifications apportées aux conditions générales'),
      '#description' => '',
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
      '#tree'        => TRUE,
    );
    $form['changes_new']['bullet_points'] = array(
      '#markup' => $form['changes']['bullet_points']['#markup'],
    );
    $output .= drupal_render($form['changes_new']);
    unset($form['changes']);
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

/*
 * Improvised function to check belgian numbers. This should be eventually removed.
 */
function devis_field_phone_belgium_validate($form, $form_state) {
  if (isset($form_state['values']['field_phone_belgium'])) {
    $lang = $form['field_phone_belgium']['#language'];
    $phonenumber = $form_state['values']['field_phone_belgium'][$lang][0]['value'];
    if (!trois_devis_valid_be_phone_number($phonenumber)) {
      form_set_error('field_phone_belgium]['. $lang .'][0][value', t('The specified number is not correct. Your number should start with 0 or +32.'));
    }
  }
}

/*
 * Function to check the TVA.
 */
function devis_field_tva_validate($form, $form_state) {
  if (isset($form['field_tva'])) {
    $lang = $form['field_tva']['#language'];
    if (strlen($form_state['values']['field_tva'][$lang][0]['value']) < 9) {
      form_set_error('field_tva]['. $lang .'][0][value', t("Votre numéro de TVA ou d'entreprise doit comporter 9 chiffres"));
    }
  }
}


?>