<?php
// Only run when WP_UNINSTALL_PLUGIN is set
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

// Remove plugin options
delete_option( 'confirm-user-registration' );

// Remove user meta
$users = get_users();

if ( $users ) :

	foreach ( $users as $user ) :

		delete_user_meta( $user->ID, 'authentication' );

	endforeach;

endif;