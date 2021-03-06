
jQuery(document).ready( function() {
  
  // date pickers
  jQuery('#wcfsl-admin input[name="start_date"], #wcfsl-admin input[name="end_date"]').datepicker({dateFormat: "yy-mm-dd"});
  
  // checking status of printer service
  if ( jQuery('#wcfsl-admin').length > 0 ) {
    jQuery.ajax( 'http://localhost:8080/status' ).done(function(response) {
      if ( response == 'ready' ) {
        jQuery('#printer_status').html('Ready');
      }
      else {
        jQuery('#printer_status').html('Invalid Response');
      }
    }).fail(function() {
      jQuery('#printer_status').html('Not Ready');
    });
  }
  
  // Generate shipping labels handler
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
    awesomeAjaxWorker.init({
      queueName: 'generateLabels',
      ajaxAction: 'generate_shipping_label',
      sequentialWorkers: 3,
      orderIds: orderIds,
      callback: function() {
        statusModal.addText('done! Reloading page');
        location.reload();
      }
    });
    awesomeAjaxWorker.doit();
  });
  
  
  // printing the label handler
  jQuery('#print-shipping-labels').click( function(e) {
    e.preventDefault();
    
    if ( jQuery('#printer_status').html() != 'Ready' ) {
      alert('Printer service not ready');
      return;
    }
    
    orderIds = new Array();
    jQuery('input[name="order[]"]:checked').each( function() {
      orderIds.push( jQuery(this).val() );
    });
    
    if ( orderIds.length < 1 ) {
      alert('No orders were checked');
      return;
    }
    
    statusModal.show('Printing shipping labels...');
    
    var data = {
      action: 'get_label_print_commands',
      order_ids: orderIds.join(',')
    };
    
    jQuery.post(wcfsl.ajaxurl, data, function( response ) {
      if (response.error) {
        alert('Error: ' + response.error);
        statusModal.close();
      }
      else if (response.print_commands) {
        statusModal.addText('sending commands to printer...');
        jQuery.post('http://localhost:8080/print', { data: response.print_commands }, function( response ) {
          statusModal.addText('done! Closing modal');
          setTimeout( function() {
            statusModal.close();
          }, 1000);
        });
      }
    });
  });
  
  
  // Mark orders complete handler
  jQuery('#mark-orders-complete').click( function(e) {
    e.preventDefault();
    orderIds = new Array();
    jQuery('input[name="order[]"]:checked').each( function() {
      orderIds.push( jQuery(this).val() );
    });
    
    if ( orderIds.length < 1 ) {
      alert('No orders were checked');
      return;
    }
    
    statusModal.show('Marking orders complete...');
    awesomeAjaxWorker.init({
      queueName: 'markOrdersComplete',
      ajaxAction: 'mark_order_complete',
      sequentialWorkers: 5,
      orderIds: orderIds,
      callback: function() {
        statusModal.addText('done! Reloading page');
        location.reload();
      }
    });
    awesomeAjaxWorker.doit();
  });
  
});


var awesomeAjaxWorker = {
  
  queueName: null,
  ajaxAction: null,
  sequentialWorkers: null, // how many concurrent workers
  orderIds: null,
  callback: null,
  
  _isBusy: false,
  _completedJobsCounter: 0,
  
  init: function(options) {
    if (this.isBusy) return;
    this.queueName = options.queueName;
    this.ajaxAction = options.ajaxAction;
    this.sequentialWorkers = options.sequentialWorkers;
    this.orderIds = options.orderIds;
    this.callback = options.callback;
  },
  
  doit: function() {
    if (this._isBusy) return;
    if ( this.orderIds.length < 1 ) {
      this._doCallback();
      return;
    }
    
    this._isBusy = true;
    this._completedJobsCounter = 0;
    
    // add all jobs
    for (var i = 0; i < this.orderIds.length; i++) {
      this._addJob(this.orderIds[i]);
    }
    
    // start queue processing
    for (var i = 0; (i < this.sequentialWorkers && i < this.orderIds.length); i++) {
      jQuery(document).dequeue(this.queueName);
    }
  },
  
  _addJob: function(orderId) {
    var THIS = this;
    var data = {
      action: this.ajaxAction,
      order_id: orderId
    };
    jQuery(document).queue(this.queueName, function() {
      jQuery.post(wcfsl.ajaxurl, data, function( response ) {
        THIS._jobCompleted(response);
      });
    });
  },
  
  
  _jobCompleted: function(response) {
    statusModal.addText('.');
    this._completedJobsCounter++;
    if ( jQuery(document).queue(this.queueName).length > 0 ) {
      jQuery(document).dequeue(this.queueName);
    }
    else if (this._completedJobsCounter == this.orderIds.length) {
      this._doCallback();
      this._isBusy = false;
    }
  },
  
  _doCallback: function() {
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
