jQuery(document).ready(function() {
	var $ = jQuery;
	$.registrationform = {
		init: function() {  
			$(".ajax-registration-form").each(function() {
				var ajaxform = $(this);
				$(this).submit(function(event) {
				event.preventDefault();
				//Clear all form errors
				$(this).find(".ajax-registration-form input").removeClass('error');
				//Update status message
				var regmessage = $(this).find(".registration-status-message");
				regmessage.removeClass('error').addClass('success').html(ajaxregistration.waiting);
				//Disable submit button
				var regsubmit = $(this).find(".ajax-submit");
				regsubmit.attr("disabled", "disabled");
				//Serialize form data
				//var form_data = $(this).find("input").serializeArray();
				var form_data = $(this).serializeArray();
				form_data = $.param(form_data);
				//alert(form_data);
				// regnonce = $(this).find("._registration_nonce");
				//regnonce = $('#_registration_nonce').val();
				//alert(regnonce);
				//Submit ajax request
				$.post( ajaxregistration.Ajax_Url, { "action" : "submitajaxregistration", "ajax_form_data" : form_data, "_ajax_nonce": $('#_registration_nonce').val() },
					function(data){
						// stop errors when Firebug is disabled
                                                // console.log('Got this JSON data - '+ data);
						if (data.errors) {
							//form errors
							//re-enable submit button
							//alert('error');
							regsubmit.removeAttr("disabled");
							var html = '';
							var no_ref_errors;
							$.each(data.errors, function(i) {
								var error_code = i;
								$.each(this, function(ii,e) {
									if(error_code == "email" && e == ajaxregistration.inuse){
									  var referrers = ['facebook.com'];
									  for(var j=0; j<5; j++) {
									    if (document.referrer.indexOf(referrers[j]) > -1) {
										if(ajaxform.parent().parent().fancybox) {
										  $.fancybox.close();
										  // window.setTimeout($.fancybox.close(), 1000);
										}
									    no_ref_errors = true;
									    return;
									    }
									  } 
									}
									//alert(i);
									//alert(e);
									$("#" + error_code).addClass('error');
									html = html + e + '<br />';
								});
							});
							if (no_ref_errors != true){
								regmessage.removeClass('success').addClass('error').html(html);
							}else{
								html = ajaxregistration.success;
								regmessage.html(html);
							}
							if(ajaxform.parents().fancybox) {
							  // alert(ajaxform.parents());
							  $.fancybox.resize();
							  }
						} else {
							//no errors
							//alert('OK');
								regmessage.addClass('success').html(data.data);
								if(ajaxform.parent().parent().fancybox) {
								  //alert('buh');
								  html = ajaxregistration.thankyou+'<br /><a href="#" onClick="javascript: $.fancybox.close();">'+ajaxregistration.go+'</a>';
								  regmessage.html(html);
								  $.fancybox.close();
								  // $.fancybox.resize().delay(500).close();
								  // window.setTimeout($.fancybox.close(), 1000);
								}
								return;							
						}
						
				}, 'json');
				return false;
			    });
			});
		}
	}; //end .registrationform
	$.registrationform.init();
	$('.wpsc_checkout_forms input[type=submit].make_purchase.wpsc_buy_button').removeAttr('disabled', 'disabled');
	$('.paypal_express_form form input[type=submit].payconfirm').removeAttr('disabled', 'disabled');
	$('.wpsc-transaction-details .wrap input[type=submit].payconfirm').removeAttr('disabled', 'disabled');
	$('.wpsc_checkout_forms input[type=submit].make_purchase.wpsc_buy_button').click(function() {
	    $(this).attr('disabled', 'disabled');
	    $(this).parents('form').submit();
	});
	$('form.wpsc_checkout_forms').submit(function(){
		$(':submit', this).attr('disabled','disabled').val(ajaxregistration.Ajax_Url).css({'cursor':'wait'});
		$(this).submit(function() {
			return false;
		});
		return true;
	});
	$('.paypal_express_form form input[type=submit].payconfirm').click(function() {
	    $(this).attr('disabled', 'disabled');
	    $(this).parents('form').submit();
	});
	$('.wpsc-transaction-details .wrap input[type=submit].payconfirm').click(function() {
	    $(this).attr('disabled', 'disabled');
	    $('.paypal_express_form form').submit();
	});
	$('.paypal_express_form form').submit(function(){
		$(':submit', this).attr('disabled','disabled').val(ajaxregistration.Ajax_Url).css({'cursor':'wait'});
		$('.wpsc-transaction-details .wrap input[type=submit].payconfirm').attr('disabled','disabled').val(ajaxregistration.Ajax_Url).css({'cursor':'wait'});
		$(this).submit(function() {
			return false;
		});
		return true;
	});
	
});