<?php

/**
 * Implements theme_preprocess_views_view_table().
 */
function devis_preprocess_views_view_table(&$variables) {
  // Budgets received by user and Budgets accepted by user.
  switch ($variables['view']->name) {
    case 'budget_provider_association':
      // Update header names.
      foreach ($variables['header'] as $key => $val) {
        $variables['header'][$key] = t($val);
        if ($val == 'Courriel') {
          $variables['header'][$key] = 'E-mail';
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
            if (($time - $res->devis_provider_assoc_contacted) > (48 * 60 * 60)) {
              $variables['rows'][$key]['url_accept'] = '<em>'. t('Expired') .'</em>';
              $variables['field_classes']['url_accept'][$key] .= ' Denied';
            }
          }
          break;
        
        case 'budgets_accepted_by_user':
          foreach ($variables['result'] as $key => $res) {
            // Change the layout of the budget entityform ID.
            $entityform = $res->_field_data['entityform_devis_provider_assoc_entityform_id']['entity'];
            $id = trois_devis_get_entityform_print_id($entityform);  
            $link = l($id, 'devis_info/'. $res->devis_provider_assoc_url_info, array('attributes' => array('target'=>'_blank')));
            $variables['rows'][$key]['entityform_id'] = $link;
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
        $period = format_date(strtotime($res['created'] .' - 1 month'), 'custom', 'F Y');
        $variables['rows'][$key]['created'] = $period;
        $date = format_date(strtotime($res['created']), 'custom', 'j F Y');
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
        case 'accountant_list':
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
        break;
      }
      break;
  }
}