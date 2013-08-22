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

	jQuery('.doaction').click(function(e){
		if ( 'delete' == jQuery('select[name="action"]').val() )
			return confirm( CUR.delete_users_warning );
	});
});