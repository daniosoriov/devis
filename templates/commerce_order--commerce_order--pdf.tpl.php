<?php

/**
 * @file
 * Template for invoiced orders.
 */
/*
?>

<div class="invoice-invoiced">
  <div class="header">
    <img src="<?php print $content['invoice_logo']['#value']; ?>"/>
    <div class="invoice-header">
        <p><?php print render($content['invoice_header']); ?></p>
    </div>
  </div>

  <div class="invoice-header-date"><?php print render($content['invoice_header_date']); ?></div>
  <div class="customer"><?php print render($content['commerce_customer_billing']); ?></div>
  <div class="invoice-number"><?php print render($content['order_number']); ?></div>
  <div class="order-id"><?php print render($content['order_id']); ?></div>

  <div class="line-items remove-br">
    <div class="line-items-view remove-br"><?php print render($content['commerce_line_items']); ?></div>
    <div class="order-total remove-br"><?php print render($content['commerce_order_total']); ?></div>
  </div>
  <div class="invoice-text"><?php print render($content['invoice_text']); ?></div>

  <div class="invoice-footer"><?php print render($content['invoice_footer']); ?></div>
</div>



<?php

/**
 * @file
 * Template for invoiced orders.
 */

?>

<div class="invoice-invoiced remove-br">
  <div class="header">
    <img src="<?php print $content['invoice_logo']['#value']; ?>" height="72" width="200"/>
    <div class="invoice-header">
      <!--<p><?php print render($content['invoice_header']); ?></p>-->
    </div>
    <h3 class="invoice-number"><?php print render($content['order_number']); ?></h3>
  </div>

  <hr/>
  <div class="customer">
    <h3><?php print t('Client'); ?></h3>
    <div class="customer-details"><?php print render($content['commerce_customer_billing']); ?></div>
  </div>
  <div class="invoice-date">
    <h3><?php print t('Invoice date'); ?></h3>
    <div class="invoice-date-date"><?php print render($content['invoice_header_date']); ?></div>
    <h3><?php print t('Period'); ?></h3>
    <div class="invoice-date-date"><?php print render($content['invoice_header_period']); ?></div>
  </div>
  <div class="clear"></div>
  <!--<div class="order-id"><?php print render($content['order_id']); ?></div>-->

  <div class="line-items">
    <div class="line-items-view"><?php 
      // This is an awful solution to fix the annoying problem given by billy Pdf.
      print_r($content['commerce_line_items'][0]['#markup']);
      //print render($content['commerce_line_items']); ?>
    </div>
    <div class="order-total"><?php print render($content['commerce_order_total']); ?></div>
  </div>
  <div class="invoice-text"><?php print render($content['invoice_text']); ?></div>

  <div class="invoice-footer"><?php print render($content['invoice_footer']); ?></div>
</div>
