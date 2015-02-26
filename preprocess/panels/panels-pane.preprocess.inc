<?php

/**
 * Implements theme_preprocess_panels_pane().
 */
function devis_preprocess_panels_pane(&$variables) {
  // TODO: Remove this eventually, hopefully.
  // This is a stupid hack made to fix a mistake with panels
  // that adds the site name at the end of the page title.
  if (isset($variables['title'])) {
    // Get the current site name to see if the title has it.
    $site_name = variable_get('site_name', '');
    $bad = '| '. $site_name;
    $pos = strpos($variables['title'], $bad);
    // If the title is compromised, removed the site name.
    if ($pos !== false) {
      $len = 0 - intval(strlen(utf8_decode($bad)));
      $variables['title'] = substr($variables['title'], 0, $len);
    }
  }
  
  
  // Beautify ask for a password.
  if (isset($variables['content']['system_main']['#id']) && $variables['content']['system_main']['#id'] == 'user-pass') {
    $variables['content']['system_main']['name']['#access'] = FALSE;
    $variables['title'] = t('Forgot password?');
    $variables['description'] = t('Type in your e-mail address and we will send you an e-mail with instructions.');
  }
  
  // Terms and conditions.
  if (isset($variables['content']['system_main']['legal']) && isset($variables['content']['system_main']['#id']) && $variables['content']['system_main']['#id'] == 'legal-login') {
    $variables['title'] = $variables['content']['system_main']['legal']['#title'] = t('Comment ça marche - Combien ça coûte');
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
      $val = $variables['content']['system_main']['main']['#markup'];
      $val = str_replace(array('ending in', 'Expires'), array(t('ending in'), t('Expires')), $val);
      $variables['content']['system_main']['main']['#markup'] = $val;
    }
    // Orders.
    if ($path == 'user/'. $uid .'/orders') {
      $variables['title'] = t('Invoices');
    }
  }
}