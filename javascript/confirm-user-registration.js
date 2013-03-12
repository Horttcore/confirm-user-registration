jQuery(function(){

	// Check all
	jQuery('input[name=check-all]').click(function(){
		var obj = jQuery(this);
		if ( obj.attr('checked') ) {
			jQuery('.widefat input[type=checkbox]:not(input[disabled=disabled])').attr('checked', 'checked');
		} else {
			jQuery('.widefat input[type=checkbox]:not(input[disabled=disabled])').removeAttr('checked');
		}
	});

	// Fix check all
	jQuery('.widefat tbody input[type=checkbox]').click(function(){
		var obj = jQuery(this);
		if ( !obj.attr('checked') ) {
			jQuery('input[name=check-all]').removeAttr('checked');
		}
	});

	// Save Settings
	jQuery('#save-settings').click(function(e){
		e.preventDefault();
		jQuery('.message').remove();
		var obj = jQuery(this);
		if ( !obj.hasClass('loading') ) {
			var form = jQuery('#confirm-user-registration-settings');
			obj.addClass('loading');
			jQuery.post( ajaxurl, form.serialize() + '&action=confirm-user-registration-save_settings', function(response) {
				obj.removeClass('loading');
				if ( '1' == response ) {
					form.find('h2').after('<div class="message updated"><p>' + form.data('success') + '</p></div>');
				} else {
					form.find('h2').after('<div class="message error"><p>' + form.data('error') + '</p></div>');
				}
				setTimeout(function(){
					jQuery('.message').slideUp(function(){
						jQuery(this).remove();
					});
				}, 3000);
			});
		}
	});

	// Auth | Block Users
	jQuery('.doaction').click(function(e){
		e.preventDefault();

		var obj = jQuery(this),
			objs = jQuery('.doaction');

		if ( !obj.hasClass('loading') ) {
			var data = {};
			data.user_ids = [];
			data.action = ( obj.data('value') == 'block' ) ? 'confirm-user-registration-block_users' : 'confirm-user-registration-auth_users';
			jQuery('tbody input:checked').each(function(){
				data.user_ids.push(jQuery(this).val());
			});
			if ( data.user_ids.length > 0 ) {
				objs.addClass('loading');
				jQuery.post( ajaxurl, data, function(response){
					objs.removeClass('loading');
					
					jQuery(data.user_ids).each(function(i,e){
						jQuery('#user-' + e).slideUp(function(){
							jQuery(this).remove();
						});
					});

				});
			}
		}
	});
});