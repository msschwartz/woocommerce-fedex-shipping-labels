<div class="wrap wcfsl">
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
      <select name="item_count">
        <option value="">-- Item Count --</option>
        <option value="single" <?php echo ( $_GET['item_count'] == 'single' ? 'selected="selected"' : ''); ?>>1</option>
        <option value="multiple" <?php echo ( $_GET['item_count'] == 'multiple' ? 'selected="selected"' : ''); ?>>2+</option>
      </select>
      <input type="submit" class="button" value="Filter">
    </div>
    <hr />
    
    <div>
      <div style="float:right">
        <strong>Printer Status: </strong><em id="printer_status">checking...</em>
      </div>
      <strong>Pending Orders</strong> (<?php echo count($orders); ?>)<br />
      <table class="widefat fixed" cellspacing="0">
        <thead>
          <tr>
            <th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox" name="checkall" /></th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Order</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Item Count</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Name</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Address</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Date</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Label Status</th>
          </tr>
        </thead>
        
        <tbody>
            
          <?php if ( count( $orders ) > 0 ) : ?>
            <?php $counter = 0; ?>
            <?php foreach ($orders as $order) : ?>
              <?php $meta = get_post_meta( $order->ID ); ?>
              <tr class="<?php echo ($counter % 2 == 0 ? 'alternate' : ''); ?>" data-row-order-id="<?php echo $order->ID; ?>">
                <th class="check-column" scope="row"><input type="checkbox" name="order[]" value="<?php echo $order->ID; ?>" /></th>
                <td class="column-columnname">
                  <!-- Order ID -->
                  <a href="post.php?post=<?php echo $order->ID; ?>&action=edit" target="_blank">#<?php echo $order->ID; ?></a>
                </td>
                <td class="column-columnname">
                  <!-- Item Count -->
                  1 Item
                </td>
                <td class="column-columnname">
                  <!-- Name -->
                  <?php echo $meta['_shipping_first_name'][0] . ' ' . $meta['_shipping_last_name'][0] ?>
                </td>
                <td class="column-columnname">
                  <!-- Address -->
                  <?php echo $meta['_shipping_address_1'][0] . ' ' . $meta['_shipping_address_2'][0] ?><br />
                  <?php echo $meta['_shipping_city'][0] . ', ' . $meta['_shipping_state'][0] . $meta['_shipping_postcode'][0] ?>
                </td>
                <td class="column-columnname">
                  <!-- Order Date -->
                  <?php echo $order->post_date; ?>
                </td>
                <td class="column-columnname">
                  <!-- Label Status -->
                  <span class="label-status label-status-<?php echo ( empty($meta['tracking_number'][0]) ? 'empty' : 'generated' ); ?>">
                    <?php echo ( empty($meta['tracking_number'][0]) ? 'empty' : 'generated' ); ?>
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
  
</div>
