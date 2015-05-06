
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
    statusModal.show('Printing shipping labels...');
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
