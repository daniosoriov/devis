<?php

/**
 * Implements theme_preprocess_views_view_table().
 */
function devis_preprocess_views_view_table(&$variables) {
  // Budgets received by user and Budgets accepted by user.
  switch ($variables['view']->name) {
    case 'budget_provider_association':
      global $user;
      if (($variables['view']->current_display == 'budgets_accepted_admin') && !in_array('manager', array_values($user->roles))) {
        drupal_access_denied();
      }
      
      // Update header names.
      foreach ($variables['header'] as $key => $val) {
        $variables['header'][$key] = t($val);
        if ($val == 'Courriel') {
          $variables['header'][$key] = 'E-mail';
        }
        if (($val == 'Provider' || $val == 'Price') && $variables['view']->current_display == 'budgets_accepted_admin') {
          $variables['header'][$key] = $val;
        }
      }
      $time = time();
      switch ($variables['view']->current_display) {
        case 'budgets_received_by_user':
          foreach ($variables['result'] as $key => $res) {
            // Fix last name.
            $lastname = ucfirst(substr($res->field_field_name[0]['raw']['safe_value'], 0, 1));
            $variables['rows'][$key]['field_prenom'] .= ' '. $lastname .'.';
            
            // Fix the status of the request.
            if ($time > $res->devis_provider_assoc_until_date) {
              $variables['rows'][$key]['url_accept'] = '<em>'. t('Expirée') .'</em>';
              $variables['field_classes']['url_accept'][$key] .= ' Denied';
            }
          }
          break;
        
        case 'budgets_accepted_by_user':
        case 'budgets_accepted_admin':
          foreach ($variables['result'] as $key => $res) {
            // Change the layout of the budget entityform ID.
            $entityform = $res->_field_data['entityform_devis_provider_assoc_entityform_id']['entity'];
            $id = trois_devis_get_entityform_print_id($entityform);  
            $link = l($id, 'devis_info/'. $res->devis_provider_assoc_url_info, array('attributes' => array('target'=>'_blank')));
            $variables['rows'][$key]['entityform_id'] = $link;
            
            // Put the price in the row.
            if ($entityform->field_devis_product) {
              $field = field_get_items('entityform', $entityform, 'field_devis_product');
              $product = commerce_product_load($field[0]['product_id']);
              $price = commerce_product_calculate_sell_price($product);
              $price_display = commerce_currency_format($price['amount'], $price['currency_code'], $product);
              $field_name = ($variables['view']->current_display == 'budgets_accepted_by_user') ? 'nothing' : 'field_devis_product';
              $variables['rows'][$key][$field_name] = $price_display;
            }
          }
          break;
      }
      break;
    
    case 'commerce_user_orders':
      // Update header names.
      foreach ($variables['header'] as $key => $val) {
        $variables['header'][$key] = t($val);
      }
      // Update period.
      foreach ($variables['rows'] as $key => $res) {
        $period = format_date(strtotime($res['created']), 'custom', 'F Y');
        $variables['rows'][$key]['created'] = $period;
        $date = strtolower(format_date(strtotime($res['created'] .' +1 month'), 'custom', '1 F Y'));
        $variables['rows'][$key]['field_commerce_billy_i_date'] = $date;
      }
      break;
    
    case 'commerce_line_item_table':
      // Update header names.
      foreach ($variables['header'] as $key => $val) {
        if ($val == 'Titre') {
          $val = 'Details';
        }
        $variables['header'][$key] = t($val);
      }
      break;
    
    case 'provider':
      switch ($variables['view']->current_display) {
        case 'provider_report_list':
          $change = array(
            'field_contacted_this_month' => 'Contacted this month',
            'field_contacted_total' => 'Contacted total',
            'field_accepted_this_month' => 'Accepted this month',
            'field_accepted_total' => 'Accepted total',
          );
          foreach ($variables['header'] as $key => $label) {
            if (!array_key_exists($key, $change)) continue;
            $variables['header'][$key] = '<div title="'. $change[$key] .'">'. $label .'</div>';
          }
          foreach ($variables['rows'] as $key => $res) {
            $date = format_date($variables['result'][$key]->users_created, 'month_short_year', '', NULL, 'en');
            $variables['rows'][$key]['created'] = $date;
            
            $status = $variables['rows'][$key]['field_account_activity_status'];
            $new_status = '<span class="'. (($status == 'Actif') ? 'Approved' : 'Denied') .'">'. $status .'</span>';
            $variables['rows'][$key]['field_account_activity_status'] = $new_status;
          }
        break;
      }
      break;
      
    case 'promo_code_usage':
      if ($variables['view']->current_display == 'promo_code_usage_admin') {
        // Update prices.
        foreach ($variables['rows'] as $key => $res) {
          $variables['rows'][$key]['discount'] = number_format($variables['rows'][$key]['discount'] / 100, 2, ',', '') .' €';
          $variables['rows'][$key]['code_usage'] = number_format($variables['rows'][$key]['code_usage'] / 100, 2, ',', '') .' €';
        }
      }
      break;
      
    case 'provider_request_list':
    case 'budget_list':
      // Change the column values to a smaller value.
      foreach ($variables['rows'] as $key => $res) {
        $pieces = explode(' ', $res['label']);
        $type = ucwords(array_pop($pieces));
        $variables['rows'][$key]['label'] = $type;
      }
      break;
  }
}