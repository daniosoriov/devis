<?php

/**
 * Implements theme_preprocess_views_view_table().
 */
function devis_preprocess_views_view_table(&$variables) {
  switch ($variables['view']->name) {
    case 'entityform_by_user':
      $args = arg();
      $uid = $args[1];
      $list = trois_devis_user_get_hash_list($uid);
      foreach ($variables['result'] as $key => $res) {
        // Change the layout of the budget entityform ID.
        $entityform = $res->_field_data['entityform_id']['entity'];
        $id = trois_devis_get_entityform_print_id($entityform);  
        $entityform_id = $entityform->entityform_id;
        $hash = $list[$entityform_id]->url_info;
        $link = l($id, 'devis_info/'. $hash, array('attributes' => array('target'=>'_blank')));
        $variables['rows'][$key]['entityform_id'] = $link;
        
        // Put the viewed date.
        $date = $list[$entityform_id]->date;
        $variables['rows'][$key]['nothing'] = ($date) ? format_date($date, 'medium', '', NULL, NULL) : '-';
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
          $val = 'Product';
        }
        $variables['header'][$key] = t($val);
      }
      break;
  }
}