
jQuery(document).ready( function() {
  
  // date pickers
  jQuery('.wcfsl input[name="start_date"], .wcfsl input[name="end_date"]').datepicker({dateFormat: "yy-mm-dd"});
  
  // checking status of printer service
  jQuery.ajax( 'http://localhost:8080/status' ).done(function(response) {
    if ( response == 'ready' ) {
      jQuery('#printer_status').html('Ready');
    }
    else {
      jQuery('#printer_status').html('Invalid Response');
      alert( 'Received an invalid response from port 8080' );
    }
  }).fail(function() {
    jQuery('#printer_status').html('Not Ready');
    alert( 'Printer service not found, please make sure it\'s running' );
  });
  
  
  jQuery('#generate-shipping-labels').click( function(e) {
    e.preventDefault();
    orderIds = new Array();
    jQuery('input[name="order[]"]:checked').each( function() {
      orderIds.push( jQuery(this).val() );
    });
    
    if ( orderIds.length < 1 ) {
      alert('No orders were checked');
      return;
    }
    
    showStatusModal('Generating shipping labels. Please wait...');
    generateLabels( orderIds, function() {
      addStatusText('done! Reloading page');
      location.reload();
    });
  });
  
  jQuery('#print-shipping-labels').click( function(e) {
    e.preventDefault();
    showStatusModal('Printing shipping labels...');
  });
  
  jQuery('#mark-orders-complete').click( function(e) {
    e.preventDefault();
    showStatusModal('Marking orders complete...');
  });
  
  
  function showStatusModal(statusText) {
    var backdrop = jQuery('<div></div>');
    backdrop.addClass('wcfsl-backdrop');
    jQuery('body').append(backdrop);
    
    var modal = jQuery('<div></div>');
    modal.addClass('wcfsl-modal');
    modal.html(statusText);
    jQuery('body').append(modal);
  }
  
  
  function addStatusText(text) {
    jQuery('.wcfsl-modal').append(text);
  }
  
  
  function closeStatusModal() {
    jQuery('.wcfsl-modal').remove();
    jQuery('.wcfsl-backdrop').remove();
  }
  
  
  // sequential ajax requests using jQuery queue
  function generateLabels( orderIds, callback ) {
    var queueName = 'generateLabels';
    for (var i = 0; i < orderIds.length; i++) {
      addGenerateLabelJob( orderIds[i], callback );
    }
    jQuery(document).dequeue(queueName);
  }
  
  
  function addGenerateLabelJob( orderId, callback ) {
    var queueName = 'generateLabels';
    var data = {
      action: 'generate_shipping_label',
      order_id: orderId
    };
    jQuery(document).queue(queueName, function() {
      jQuery.post(wcfsl.ajaxurl, data, function( response ) {
        console.log(response);
        addStatusText('.');
        if ( jQuery(document).queue(queueName).length == 0 ) {
          callback();
        }
        else {
          jQuery(document).dequeue(queueName);
        }
      });
    });
  }
  
  
});
