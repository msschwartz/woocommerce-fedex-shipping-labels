<div id="wcfsl-admin" class="wrap">
  <div style="float:right">
    <button class="button" id="generate-shipping-labels">Generate Shipping Labels</button>
    <button class="button" id="print-shipping-labels">Print Shipping Labels</button>
    <button class="button" id="mark-orders-complete">Mark Orders Complete</button>
  </div>
  <h2>FedEx Shipping Labels</h2>
  <hr />
  
  <form action="#">
    <input type="hidden" name="page" value="fedex-shipping-labels" />
  
    <div>
      <strong>Filters</strong>
      <input type="text" name="start_date" autocomplete="off" placeholder="Start date" value="<?php echo $_GET['start_date'] ?>" />
      <input type="text" name="end_date" autocomplete="off" placeholder="End date" value="<?php echo $_GET['end_date'] ?>" />
      <select name="product_id">
        <option value="">-- Product --</option>
        <?php foreach ($products as $product) : ?>
          <option value="<?php echo $product->ID; ?>" <?php echo ( $_GET['product_id'] == $product->ID ? 'selected="selected"' : ''); ?>><?php echo $product->post_title; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="quantity">
        <option value="">-- Quantity --</option>
        <option value="single" <?php echo ( $_GET['quantity'] == 'single' ? 'selected="selected"' : ''); ?>>1</option>
        <option value="multiple" <?php echo ( $_GET['quantity'] == 'multiple' ? 'selected="selected"' : ''); ?>>2+</option>
      </select>
      <input type="submit" class="button" value="Filter">
    </div>
    <hr />
    
    <div>
      <div style="float:right">
        <strong>Printer Status: </strong><em id="printer_status">checking...</em>
      </div>
      <strong>Pending Orders (<?php echo count($orders); ?>)</strong><br />
      <table class="widefat fixed" cellspacing="0">
        <thead>
          <tr>
            <th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox" name="checkall" /></th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Order</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Product</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Quantity</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Name</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Address</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Date</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Label Status</th>
          </tr>
        </thead>
        
        <tbody>
        
          <?php if ( count( $orders ) > 0 ) : ?>
            <?php $counter = 0; ?>
            <?php foreach ($orders as $order) :
                $wc_order = wc_get_order( $order->ID );
                $item = array_pop( $wc_order->get_items() );
                $meta = get_post_meta( $order->ID );
                
                if ( ! empty( $meta['shipping_label_error'][0] ) ) {
                  $label_status_css = 'error';
                  $label_status_text = $meta['shipping_label_error'][0];
                }
                else if ( ! empty( $meta['tracking_number'][0] ) ) {
                  $label_status_css = 'good';
                  $label_status_text = 'generated';
                }
                else {
                  $label_status_css = 'warning';
                  $label_status_text = 'empty';
                }
                
                // filter product_id and quantity here... (query was too messy)
                if ( !empty($_GET['product_id']) ) {
                  if ( $item['product_id'] != $_GET['product_id'] ) {
                    continue;
                  }
                }
                if ( !empty($_GET['quantity']) ) {
                  if( $_GET['quantity'] == 'single' && $item['qty'] > 1 ) {
                    continue;
                  }
                  if( $_GET['quantity'] == 'multiple' && $item['qty'] < 2 ) {
                    continue;
                  }
                }
              ?>
              
              <tr class="<?php echo ($counter % 2 == 0 ? 'alternate' : ''); ?>" data-row-order-id="<?php echo $order->ID; ?>">
                <th class="check-column" scope="row"><input type="checkbox" name="order[]" value="<?php echo $order->ID; ?>" /></th>
                <td class="column-columnname">
                  <!-- Order ID -->
                  <a href="post.php?post=<?php echo $order->ID; ?>&action=edit" target="_blank">#<?php echo $order->ID; ?></a>
                </td>
                <td class="column-columnname">
                  <!-- Product -->
                  <?php echo $item['name']; ?>
                </td>
                <td class="column-columnname">
                  <!-- Item Count -->
                  <?php echo $item['qty']; ?>
                </td>
                <td class="column-columnname">
                  <!-- Name -->
                  <?php echo $wc_order->shipping_first_name . ' ' . $wc_order->shipping_last_name ?>
                </td>
                <td class="column-columnname">
                  <!-- Address -->
                  <?php echo $wc_order->shipping_address_1 . ' ' . $wc_order->shipping_address_2 ?><br />
                  <?php echo $wc_order->shipping_city . ', ' . $wc_order->shipping_state . ' ' . $wc_order->shipping_postcode ?>
                </td>
                <td class="column-columnname">
                  <!-- Order Date -->
                  <?php echo $order->post_date; ?>
                </td>
                <td class="column-columnname">
                  <!-- Label Status -->
                  <span class="label-status label-status-<?php echo $label_status_css; ?>">
                    <?php echo $label_status_text; ?>
                  </span>
                </td>
              </tr>
              <?php $counter++; ?>
            <?php endforeach; ?>
            
          <?php else : ?>
            
            <tr>
              <td colspan="6">No orders found. Please check the filters</td>
            </tr>
            
          <?php endif; ?>
            
        </tbody>
      </table>
    </div>
    
  </form>
  
  <p><a href="<?php echo WCFSL_BASE_URL . 'ws-zebra-printer.jar'; ?>">Download Printer Service</a>
  
</div>
