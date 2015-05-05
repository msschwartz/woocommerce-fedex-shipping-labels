<div class="wrap">
  <h2>FedEx Shipping Labels</h2>
  
  <h3>Filters</h3>
  <input type="text" name="start_date" placeholder="Start date" />
  <input type="text" name="end_date" placeholder="End date" />
  <select name="item_count">
    <option value="">-- Item Count --</option>
    <option value="single">1</option>
    <option value="multiple">2+</option>
  </select>
  <input type="submit" name="filter" class="button" value="Filter">
  <hr />
  
  <h3>Bulk Actions</h3>
  <select name="action">
    <option value="" selected="selected">-- Action --</option>
    <option value="generate-shipping-labels">Generate Shipping Labels</option>
    <option value="print-shipping-labels">Print Shipping Labels</option>
    <option value="mark-orders-complete">Mark Orders Complete</option>
  </select>
  <input type="submit" id="doaction" class="button" value="Apply">
  <hr />
  
  <h3>Pending Orders</h3>
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
          <tr class="<?php echo ($counter % 2 == 0 ? 'alternate' : ''); ?>">
            <th class="check-column" scope="row"><input type="checkbox" name="order[]" value="1234" /></th>
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
