
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
    
    statusModal.show('Generating shipping labels. Please wait...');
    labelGenerator.init( orderIds, function() {
      statusModal.addText('done! Reloading page');
      location.reload();
    });
    labelGenerator.doit();
  });
  
  jQuery('#print-shipping-labels').click( function(e) {
    e.preventDefault();
    statusModal.show('Printing shipping labels...');
  });
  
  jQuery('#mark-orders-complete').click( function(e) {
    e.preventDefault();
    statusModal.show('Marking orders complete...');
  });
  
});


var labelGenerator = {
  
  queueName: 'generateLabels',
  ajaxAction: 'generate_shipping_label',
  isBusy: false,
  orderIds: [],
  completedJobsCounter: 0,
  sequentialWorkers: 3, // how many workers do we use
  callback: null,
  
  init: function(orderIds, callback) {
    if (this.isBusy) return;
    this.orderIds = orderIds;
    this.callback = callback;
  },
  
  doit: function() {
    if (this.isBusy) return;
    if ( this.orderIds.length < 1 ) {
      this.__doCallback();
      return;
    }
    
    this.isBusy = true;
    this.completedJobsCounter = 0;
    
    // add all jobs
    for (var i = 0; i < this.orderIds.length; i++) {
      this.__addJob(this.orderIds[i]);
    }
    
    // start queue processing
    for (var i = 0; (i < this.sequentialWorkers && i < this.orderIds.length); i++) {
      jQuery(document).dequeue(this.queueName);
    }
  },
  
  __addJob: function(orderId) {
    var THIS = this;
    var data = {
      action: this.ajaxAction,
      order_id: orderId
    };
    jQuery(document).queue(this.queueName, function() {
      jQuery.post(wcfsl.ajaxurl, data, function( response ) {
        THIS.__jobCompleted(response);
      });
    });
  },
  
  
  __jobCompleted: function(response) {
    statusModal.addText('.');
    this.completedJobsCounter++;
    if ( jQuery(document).queue(this.queueName).length > 0 ) {
      jQuery(document).dequeue(this.queueName);
    }
    else if (this.completedJobsCounter == this.orderIds.length) {
      this.__doCallback();
      this.isBusy = false;
    }
  },
  
  __doCallback: function() {
    if (this.callback) {
      this.callback();
    }
  },
  
};


var statusModal = {
  
  show: function(statusText) {
    var backdrop = jQuery('<div></div>');
    backdrop.addClass('wcfsl-backdrop');
    jQuery('body').append(backdrop);
    
    var modal = jQuery('<div></div>');
    modal.addClass('wcfsl-modal');
    modal.html(statusText);
    jQuery('body').append(modal);
  },
  
  
  addText: function(text) {
    jQuery('.wcfsl-modal').append(text);
  },
  
  
  close: function() {
    jQuery('.wcfsl-modal').remove();
    jQuery('.wcfsl-backdrop').remove();
  },
  
};
