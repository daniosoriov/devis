<?php

/**
 * Implements hook_preprocess_entity().
 */
function devis_preprocess_entity(&$variables, $hook) {
  $entity_type = $variables['entity_type'];
  $view_mode = $variables['view_mode'];
  
  // Entityform demander devis.
  if ($entity_type == 'entityform' && $variables['elements']['#entity_type'] == 'entityform' && $variables['elements']['#bundle'] == 'comptable') {
    $entityform = $variables['entityform'];
    $info_list = trois_devis_entity_get_hash_list($entityform->entityform_id);
    
    // Admin fields.
    if (isset($variables['content']['group_content']['field_activity_admin'])) {
      $variables['content']['group_content']['field_activity_admin']['#prefix'] = '<div class="admin-field">';
      $variables['content']['group_content']['field_activity_admin']['#suffix'] = '</div>';
    }
    if (isset($variables['content']['group_content']['field_info_extra_admin'])) {
      $variables['content']['group_content']['field_info_extra_admin']['#prefix'] = '<div class="admin-field">';
      $variables['content']['group_content']['field_info_extra_admin']['#suffix'] = '</div>';
    }
    
    if (isset($variables['content']['group_admin']['field_devis_product'])) {
      $lang = key($entityform->field_devis_product);
      $product_id = $entityform->field_devis_product[$lang][0]['product_id'];
      $product = commerce_product_load($product_id);
      $price = commerce_product_calculate_sell_price($product);
      $price_display = commerce_currency_format($price['amount'], $price['currency_code'], $product);
      $variables['content']['group_admin']['field_devis_product'][0]['#markup'] = $price_display .' ('. $product->sku .')';
    }
    
    // Providers contacted.
    if (isset($variables['content']['group_admin']['field_provider_contacted']) && 
        $variables['content']['group_admin']['field_provider_contacted']['#access']) {
      foreach ($variables['content']['group_admin']['field_provider_contacted']['#items'] as $key => $arr) {
        if (!$arr['access']) continue;
        if (!isset($info_list[$arr['target_id']])) continue;
        $info = $info_list[$arr['target_id']];

        $link = ($info->min_left < 0) ? '<span class="red"><em>URL Expired</em></span>' : 
          l('User preview URL', 'devis_accept/'. $info->url_accept, array('attributes' => array('target' => '_blank', 'title' => 'This is the secure URL for the user to preview and accept the budget...', 'onclick' => 'return false')));
        $markup = ' -- '. $link .' - '. (($info->opened_date) ? 'viewed on '. format_date($info->opened_date, 'medium', '', NULL, 'en') : '<em>not yet viewed</em>') .'';
        $variables['content']['group_admin']['field_provider_contacted'][$key]['#markup'] .= $markup;
        $variables['elements']['group_admin']['field_provider_contacted'][$key]['#markup'] .= $markup;
        $variables['elements']['field_provider_contacted'][$key]['#markup'] .= $markup;
      }
    }
    
    // Providers accepted.
    if (isset($variables['content']['group_admin']['field_providers_accepted']) && 
        $variables['content']['group_admin']['field_providers_accepted']['#access']) {
      foreach ($variables['content']['group_admin']['field_providers_accepted']['#items'] as $key => $arr) {
        if (!$arr['access']) continue;
        if (!isset($info_list[$arr['target_id']])) continue;
        $info = $info_list[$arr['target_id']];
        
        $variables['content']['group_admin']['field_providers_accepted'][$key]['#markup'] .= ' -- '. 
          l('User budget URL', 'devis_info/'. $info->url_info, array('attributes' => array('target' => '_blank', 'title' => 'This is the secure URL for the user to view the budget...'))) 
          .' - accepted on '. format_date($info->date, 'medium', '', NULL, 'en');
      }
    }
    
    if (isset($variables['content']['group_contact']['field_company_name'])) {
      $variables['content']['group_contact']['field_company_name']['#title'] = t('Société');
    }
    $variables['content']['group_contact']['field_tva']['#access'] = 
      $variables['elements']['field_tva']['#access'] = 
      $variables['content']['group_client']['group_client_first']['field_tva']['#access'] = 
      $variables['elements']['group_client']['group_client_first']['field_tva']['#access'] = TRUE;
    if (isset($variables['content']['group_information']['field_legal_status']) && 
        $variables['content']['group_information']['field_legal_status']['#items'][0]['value'] == 'association') {
      $variables['content']['group_contact']['field_company_name']['#title'] = t('Association');
      //$variables['content']['group_contact']['field_tva']['#title'] = t('Numéro de TVA de votre association');
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
    // If it's a manager, different links on the bottom.
    $is_manager = FALSE;
    if (in_array('manager', $variables['user']->roles)) {
      $is_manager = TRUE;
    }
    $user_path = 'user/'. $variables['user']->uid;
    $variables['orders_url'] = ($is_manager) ? '' : l(t('Invoices'), $user_path .'/orders');
    $variables['account_url'] = ($is_manager) ? '' : l(t('My account'), $user_path);
    //$variables['print_url'] = l(t('Print version'), 'print/'. current_path());
    $variables['pdf_url'] = ($variables['commerce_order']->status == 'invoiced') ? l(t('PDF version'), 'invoice-pdf/'. $variables['commerce_order']->order_id) : '';
  }
  
  // Commerce Order PDF.
  $pdf_view_modes = array('pdf', 'canceled');
  if ($entity_type == 'commerce_order' && in_array($view_mode, $pdf_view_modes)) {
    //$variables['theme_hook_suggestions'][] = $entity_type . '__commerce_order__' . $view_mode;
    $order = $variables['commerce_order'];
    $variables['content']['order_number']['#markup'] = t('Invoice') .': '. $order->order_number;
    $variables['content']['order_id']['#markup'] = t('Commande') .': '. $order->order_id;
    
    $markup = $variables['content']['commerce_line_items'][0]['#markup'];
    $variables['content']['commerce_line_items'][0]['#markup'] = str_replace(array('Titre'), array(t('Details')), $markup);
    $markup = $variables['content']['commerce_order_total'][0]['#markup'];
    $variables['content']['commerce_order_total'][0]['#markup'] = str_replace(array('Order total'), array(t('Total')), $markup);
    
    $variables['content']['commerce_customer_billing']['#title'] = t('Client');
    $variables['content']['invoice_header_date']['#title'] = t('Invoice date');
    
    // Invoice date and period.
    if (isset($variables['field_commerce_billy_i_date'])) {
      $info = $variables['field_commerce_billy_i_date'][0];
      
      $new_date = strtotime(date('F Y', $order->created) .' +1 month');
      $date = strtolower(format_date($new_date, 'custom', '1 F Y', $info['timezone']));
      $variables['content']['invoice_header_date']['#markup'] = strtolower($date);
      
      $period = format_date(strtotime(date('F Y', $order->created)), 'custom', 'F Y', $info['timezone']);
      $variables['content']['invoice_header_period'] = array('#markup' => $period, '#title' => t('Period'));
    }
  }
  
  if ($entity_type == 'profile2' && $variables['elements']['#entity_type'] == 'profile2') {
    $change = array(
      'field_contacted_this_month' => 'group_contacted', 
      'field_contacted_total' => 'group_contacted', 
      'field_accepted_this_month' => 'group_accepted', 
      'field_accepted_total' => 'group_accepted', 
    );
    foreach ($change as $field => $group) {
      if (isset($variables['content']['group_admin'][$group][$field])) {
        $count = $variables[$field][0]['value'];
        $markup = ($count) ? format_plural($count, '1 time', '@count times') : t('0 times');
        $variables['elements']['group_admin'][$group][$field][0]['#markup'] = 
        $variables['elements'][$field][0]['#markup'] = 
        $variables['content']['group_admin'][$group][$field][0]['#markup'] = $markup;
      }
    }
    
    // Change the list of budgets layout for the viewed user.
    if (isset($variables['content']['group_admin']['field_entity_devis_ref']['#items'])) {
      $info_list = trois_devis_user_get_hash_list($variables['elements']['#entity']->uid);
      foreach ($variables['content']['group_admin']['field_entity_devis_ref']['#items'] as $key => $arr) {
        if (!$arr['access']) continue;
        $entity = $arr['entity'];
        if (!isset($info_list[$entity->entityform_id])) continue;
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
        $variables['elements']['group_admin']['field_entity_devis_ref'][$key]['#markup'] = 
        $variables['elements']['field_entity_devis_ref'][$key]['#markup'] = 
        $variables['content']['group_admin']['field_entity_devis_ref'][$key]['#markup'] = $markup;
      }
    }
  }
}