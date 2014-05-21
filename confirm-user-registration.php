<?php
/*
Plugin Name: Confirm User Registration
Plugin URI: http://www.horttcore.de/
Description: Admins have to confirm a user registration - a notification will be send when the account gets activated
Author: Ralf Hortt
Version: 2.1.5
Author URI: http://horttcore.de/
*/



/**
 * Security, checks if WordPress is running
 **/
if ( !function_exists('add_action') ) :
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
endif;



/**
 *
 * Plugin Definitions
 *
 */
define( 'RH_CUR_BASENAME', plugin_basename(__FILE__) );
define( 'RH_CUR_BASEDIR', dirname( plugin_basename(__FILE__) ) );



/**
*
*/
class Confirm_User_Registration
{



	/**
	 *
	 * Construct
	 *
	 */
	function __construct()
	{
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_action( 'admin_print_scripts-users_page_confirm-user-registration', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_print_styles-users_page_confirm-user-registration', array( $this, 'enqueue_styles' ) );

		add_action( 'wp_ajax_confirm-user-registration-save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ) ); # Prevent login if user is not authed

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
	}



	/**
	 * Plugin activation
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function activation()
	{
		// First time installation
		if ( $this->is_first_time() ) :

			$users = get_users();

			if ( $users ) :

				foreach ( $users as $user ) :

					update_usermeta( $user->ID, 'authentication', '1' );

				endforeach;

			endif;

			add_option( 'confirm-user-registration', array(
				# Notifcation to admin
				'administrator' => get_bloginfo('admin_email'),
				# Notification to users
				'error' => __( '<strong>ERROR:</strong> Your account has to be confirmed by an administrator before you can login', 'confirm-user-registration' ),
				# Mail
				'from' => get_bloginfo('name').' <'.get_bloginfo('admin_email').">\n",
				'subject' => __( 'Account Confirmation: ' . get_bloginfo('name'), 'confirm-user-registration' ),
				'message' => __( "You account has been approved by an administrator!\nLogin @ ".get_bloginfo('url')."/wp-login.php\n\nThis message is auto generated\n", 'confirm-user-registration' ),
			));

		// Upgrade
		else :

			if ( $this->is_upgrade() ) :

				// Create new option array
				add_option( 'confirm-user-registration', array(
					# Notifcation to admin
					'administrator' => get_option( 'cur_administrator' ),
					# Notification to users
					'error' => get_option( 'cur_error' ),
					# Mail
					'from' => get_option( 'cur_from' ),
					'subject' => get_option( 'cur_subject' ),
					'message' => get_option( 'cur_message' )
				));

				// Cleanup
				delete_option( 'cur_administrator' );
				delete_option( 'cur_error' );
				delete_option( 'cur_from' );
				delete_option( 'cur_subject' );
				delete_option( 'cur_message' );

			endif;

		endif;
	}



	/**
	 * Add admin menu page
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function admin_menu()
	{
		add_users_page( _x( 'Confirm User Registration', 'confirm-user-registration' ), _x( 'Confirm User Registration',  'confirm-user-registration' ), 'promote_users', 'confirm-user-registration', array( $this, 'management' ) );
	}



	/**
	 * Authenticate Users
	 *
	 * @access public
	 * @param array $user_ids User IDs to confirm
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function auth_users( array $user_ids )
	{
		if ( $user_ids && current_user_can( 'promote_users' ) ) :

			foreach ( $user_ids as $user_id ) :

				if ( is_numeric( $user_id ) ) :
					update_user_meta( $user_id, 'authentication', '1' );
					do_action( 'confirm-user-registration-auth-user', $user_id );
					$this->send_notification( $user_id );
				endif;

			endforeach;

			?>
			<div class="updated message">
				<?php if ( 1 == count( $user_ids) ) : ?>
					<p><?php _e( '1 user authenticated', 'confirm-user-registration' ) ?></p>
				<?php else : ?>
					<p><?php echo count( $user_ids ) .  ' ' . __( 'users authenticated', 'confirm-user-registration' ) ?></p>
				<?php endif; ?>
			</div>
			<?php

		endif;
	}



	/**
	 * Block Users
	 *
	 * @access public
	 * @param array $user_ids User IDs to block
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function block_users( array $user_ids )
	{
		if ( $user_ids && current_user_can( 'promote_users' ) ) :

			foreach ( $user_ids as $user_id ) :

				if ( is_numeric( $user_id ) ) :
					delete_user_meta( $user_id, 'authentication' );
					do_action( 'confirm-user-registration-block-user', $user_id );
				endif;

			endforeach;

			?>
			<div class="updated message">
				<?php if ( 1 == count( $user_ids) ) : ?>
					<p><?php _e( '1 user blocked', 'confirm-user-registration' ) ?></p>
				<?php else : ?>
					<p><?php echo count( $user_ids ) .  ' ' . __( 'users blocked', 'confirm-user-registration' ) ?></p>
				<?php endif; ?>
			</div>
			<?php

		endif;
	}



	/**
	 * Bulk delete users
	 *
	 * @access public
	 * @param array $user_ids User IDs to block
	 * @return void
	 * @since 2.1
	 * @author Ralf Hortt
	 **/
	public function delete_users( array $user_ids )
	{
		if ( $user_ids && current_user_can( 'delete_users' ) ) :

			foreach ( $user_ids as $user_id ) :

				if ( is_numeric( $user_id ) ) :
					wp_delete_user( $user_id );
					do_action( 'confirm-user-registration-delete-user', $user_id );
				endif;

			endforeach;

			?>
			<div class="updated message">
				<?php if ( 1 == count( $user_ids) ) : ?>
					<p><?php _e( '1 user deleted', 'confirm-user-registration' ) ?></p>
				<?php else : ?>
					<p><?php echo count( $user_ids ) .  ' ' . __( 'users deleted', 'confirm-user-registration' ) ?></p>
				<?php endif; ?>
			</div>
			<?php

		endif;
	}


	/**
	 * Add scripts
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function enqueue_scripts()
	{
		wp_enqueue_script( 'confirm-user-registration', WP_PLUGIN_URL . '/' . RH_CUR_BASEDIR . '/javascript/confirm-user-registration.js' );
		$translation_array = array(
			'delete_users_warning' => __( 'Are you sure you want to delete these users?', 'confirm-user-registration' ),
		);
		wp_localize_script( 'confirm-user-registration', 'CUR', $translation_array );
	}



	/**
	 * Add styles
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function enqueue_styles()
	{
		wp_enqueue_style( 'confirm-user-registration', WP_PLUGIN_URL . '/' . RH_CUR_BASEDIR . '/css/confirm-user-registration.css' );
	}


	/**
	 * Get authed users
	 *
	 * @access public
	 * @return array Users
	 * @author Ralf Hortt
	 **/
	public function get_authed_users()
	{
		return get_users(array(
			'meta_key' => 'authentication',
			'meta_compage' => '=',
			'meta_value' => 1
		));
	}



	/**
	 * Get pending users
	 *
	 * @access public
	 * @return array Users
	 * @author Ralf Hortt
	 **/
	public function get_pending_users()
	{
		$users = get_users();

		$authed_users = $this->get_authed_users();

		$authed_ids = array();

		if ( $authed_users ) :

			foreach ( $authed_users as $authed_user ) :

				array_push( $authed_ids, $authed_user->ID );

			endforeach;

		endif;

		$pending_users = array();

		if ( $users ) :

			foreach ( $users as $user ) :

				if ( !in_array( $user->ID, $authed_ids ) ) :

					array_push( $pending_users, $user );

				endif;

			endforeach;

		endif;

		return $pending_users;
	}



	/**
	 * Checks if user is authenticated
	 *
	 * @access public
	 * @param int $user_id User ID
	 * @return bool
	 * @author Ralf Hortt
	 **/
	public function is_authenticated( $user_id )
	{
		if ( 1 == get_user_meta( $user_id, 'authentication', TRUE ) ) :
			return TRUE;
		else :
			return FALSE;
		endif;
	}


	/**
	 * Checks if plugin was installed before
	 *
	 * @access public
	 * @return bool
	 * @author Ralf Hortt
	 **/
	public function is_first_time()
	{
		if ( !get_option( 'cur_from' ) && !get_option( 'confirm-user-registration' ) ) :
			return TRUE;
		else :
			return FALSE;
		endif;
	}


	/**
	 * Upgrade from 1.x to < 2.0
	 *
	 * @access public
	 * @return bool
	 * @author Ralf Hortt
	 **/
	public function is_upgrade()
	{
		if ( get_option( 'cur_from' ) ) :
			return TRUE;
		else :
			return FALSE;
		endif;
	}



	/**
	 * Load plugin textdomain
	 *
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function load_plugin_textdomain()
	{
		load_plugin_textdomain( 'confirm-user-registration', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'  );
	}



	/**
	 * Management page
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function management()
	{
		?>
		<div class="wrap">

			<?php $this->management_nav(); ?>

			<?php
			$tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : '';
			if ( 'settings' == $tab ) :
				$this->management_settings();
			else :
				$this->management_users( $tab );
			endif;
			?>

		</div>
		<?php
	}



	/**
	 * Managment Nav
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function management_nav()
	{
		?>
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ( 'pending' == $_GET['tab'] || !$_GET['tab']) echo 'nav-tab-active' ?>" href="users.php?page=confirm-user-registration&amp;tab=pending"><?php _e( 'Pending Users', 'confirm-user-registration' ); ?></a>
			<a class="nav-tab <?php if ( 'authed' == $_GET['tab'] ) echo 'nav-tab-active' ?>" href="users.php?page=confirm-user-registration&amp;tab=authed"><?php _e( 'Authenticated Users', 'confirm-user-registration' ); ?></a>
			<a class="nav-tab <?php if ( 'settings' == $_GET['tab'] ) echo 'nav-tab-active' ?>" href="users.php?page=confirm-user-registration&amp;tab=settings"><?php _e( 'Settings', 'confirm-user-registration' ); ?></a>
			<a class="nav-tab" href="https://github.com/Horttcore/confirm-user-registration" target="_blank"><?php _e( 'Help' ); ?></a>
		</h2>
		<?php
	}



	/**
	 * Settings tab
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function management_settings()
	{
		$this->save_settings();
		$options = get_option( 'confirm-user-registration' );
		?>
		<form method="post" id="confirm-user-registration-settings" data-success="<?php _e( 'Settings saved', 'confirm-user-registration' ); ?>" data-error="<?php _e( '<strong>ERROR:</strong> Could not save settings', 'confirm-user-registration' ); ?>">
			<div class="icon32" id="icon-tools"><br></div><h2><?php _e( 'Confirm User Registration Settings', 'confirm-user-registration' ); ?></h2>
			<table class="form-table">
				<tr>
					<th colspan="2"><h3><?php _e( 'Login notice', 'confirm-user-registration' ); ?></h3></th>
				</tr>
				<tr>
					<th><label for="error"><?php _e( 'Error Message', 'confirm-user-registration' )?></label></th>
					<td><input size="82" type="text" name="error" id="error" value="<?php echo $options['error']; ?>"></td>
				</tr>
				<tr>
					<th colspan="2"><h3><?php _e( 'E-Mail notification', 'confirm-user-registration' ); ?></h3></th>
				</tr>
				<tr>
					<th><label for="from"><?php _e( 'From', 'confirm-user-registration' )?></label></th>
					<td><input size="82" type="text" name="from" id="from" value="<?php echo $options['from']; ?>" /></td>
				</tr>
				<tr>
					<th><label for="subject"><?php _e( 'Subject', 'confirm-user-registration' )?></label></th>
					<td><input size="82" type="text" name="subject" id="subject" value="<?php echo $options['subject']; ?>" /></td>
				</tr>
				<tr>
					<th><label for="message"><?php _e( 'Message', 'confirm-user-registration' )?></label></th>
					<td><textarea name="message" rows="8" cols="80" id="message"><?php echo $options['message']; ?></textarea></td>
				</tr>
				<?php do_action( 'confirm-user-registration-options' ) ?>
			</table>
			<p class="submit"><button id="save-settings" class="button button-primary" type="submit"><?php _e( 'Save', 'confirm-user-registration' )?></button></p>
			<?php wp_nonce_field( 'save-confirm-user-registration-settings', 'save-confirm-user-registration-settings-nonce' ) ?>
		</form>
		<?php
	}



	/**
	 * Users tab
	 *
	 * @access public
	 * @param str $tag {pending|auth} Tab to show
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function management_users( $tab )
	{
		global $user_ID;

		if ( $_POST && 'auth' == $_POST['action'] && wp_verify_nonce( $_POST['confirm-bulk-action-nonce'], 'confirm-bulk-action' ) ) :
			$this->auth_users( $_POST['users'] );
		elseif ( $_POST && 'block' == $_POST['action'] && wp_verify_nonce( $_POST['confirm-bulk-action-nonce'], 'confirm-bulk-action' ) ) :
			$this->block_users( $_POST['users'] );
		elseif ( $_POST && 'delete' == $_POST['action'] && wp_verify_nonce( $_POST['confirm-bulk-action-nonce'], 'confirm-bulk-action' ) ) :
			$this->delete_users( $_POST['users'] );
		endif;

		$users = ( 'pending' == $tab || '' == $tab ) ? $this->get_pending_users() : $this->get_authed_users();
		$title = ( 'pending' == $tab || '' == $tab ) ? __( 'Authenticate Users', 'confirm-user-registration' ) : __( 'Block Users', 'confirm-user-registration' );
		$action_data = ( 'pending' == $tab || '' == $tab ) ? 'auth' : 'block';
		?>

		<div class="icon32" id="icon-users"><br></div><h2><?php echo $title ?></h2>

		<form method="post">

			<?php wp_nonce_field( 'confirm-bulk-action', 'confirm-bulk-action-nonce' ) ?>

			<div class="tablenav top">
				<select name="action">
					<option value=""><?php _e( 'Bulk Actions' ); ?></option>
					<option value="<?php echo $action_data ?>"><?php echo $title ?></option>
					<?php if ( current_user_can( 'delete_users' ) ) : ?>
						<option value="delete"><?php _e( 'Delete' ); ?></option>
					<?php endif; ?>
				</select>
				<input type="submit" value="<?php _e( 'Apply' ); ?>" class="button action doaction" name="" data-value="<?php echo $action_data ?>">
			</div>

			<table class="widefat">
				<thead>
					<tr>
						<th id="cb"><input type="checkbox" name="check-all" valle="Check all"></th>
						<th id="gravatar"><?php _e( 'Gravatar', 'confirm-user-registration' ); ?></th>
						<th id="display_name"><?php _e( 'Name', 'confirm-user-registration' ); ?></th>
						<th id="email"><?php _e( 'E-Mail', 'confirm-user-registration' ); ?></th>
						<th id="role"><?php _e( 'Role', 'confirm-user-registration' ); ?></th>
						<th id="registered"><?php _e( 'Registered', 'confirm-user-registration' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( $users ) :
						$i = 1;
						foreach ( $users as $user ) :
							$class = ( $i % 2 == 1 ) ? 'alternate' : 'default';
							$user_data = get_userdata( $user->ID );
							$user_registered = mysql2date(get_option('date_format'), $user->user_registered);
							?>
							<tr id="user-<?php echo $user->ID ?>" class="<?php echo $class ?>">
								<th>
									<?php if ( $user->ID != $user_ID ) :?>
										<input type="checkbox" name="users[]" value="<?php echo $user->ID ?>">
									<?php endif; ?>
								</th>
								<td><img class="gravatar" src="http://www.gravatar.com/avatar/<?php echo md5( $user->user_email ) ?>?s=32"></td>
								<td>
									<a href="user-edit.php?user_id=<?php echo $user->ID ?>"><?php echo $user->display_name ?></a>
									<div class="row-actions">
										<?php if ( current_user_can( 'edit_user',  $user->ID ) ) : ?>
											<span class="edit"><a href="<?php echo admin_url( 'user-edit.php?user_id=' . $user->ID  ) ?>"><?php _e( 'Edit' ); ?></a>
										<?php endif; ?>
										<?php if ( current_user_can( 'edit_user',  $user->ID ) && current_user_can( 'delete_user', $user->ID ) && $user_ID != $user->ID ) : ?>
											&nbsp;|&nbsp;</span>
										<?php endif; ?>
										<?php if ( current_user_can( 'delete_user', $user->ID ) && $user_ID != $user->ID ) : ?>
											<span class="delete"><a href="<?php echo admin_url( 'users.php?action=delete&user=' . $user->ID . '&_wpnonce=' . wp_create_nonce( 'bulk-users' ) ) ?>"><?php _e( 'Delete' ); ?></a></span>
										<?php endif; ?>
									</div>
								</td>
								<td><a href="mailto:<?php echo $user->user_email ?>"><?php echo $user->user_email ?></a></td>
								<td>
									<?php
									if ( $user_data->roles ) :

										foreach ( $user_data->roles as $role ) :

											echo _x( ucfirst( $role ), 'User role' ) . '<br>';

										endforeach;

									endif;
									?>
								</td>
								<td><?php echo $user_registered ?></td>
							</tr>
							<?php
							$i++;
						endforeach;

					else :

						?>
						<tr>
							<td colspan="6"><strong><?php _e( 'No Users found', 'confirm-user-registration' ); ?></strong></td>
						</tr>
						<?php

					endif;
					?>
				</tbody>
			</table>

		</form>
		<?php
	}



	/**
	 * Handles save settings ajax request
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function save_settings()
	{
		if ( $_POST && wp_verify_nonce( $_POST['save-confirm-user-registration-settings-nonce'], 'save-confirm-user-registration-settings' ) ) :
			$options = array(
				'error' => $_POST['error'],
				'from' => $_POST['from'],
				'subject' => $_POST['subject'],
				'message' => $_POST['message']
			);

			$options = apply_filters( 'confirm-user-registration-save-options', $options );
			update_option( 'confirm-user-registration', $options);

			?>
			<div class="updated message">
				<p><?php _e( 'Saved' ); ?></p>
			</div>
			<?php
		endif;
	}



	/**
	 * Send notification
	 *
	 * @access public
	 * @param int $user_id User ID
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function send_notification( $user_id )
	{
		$options = get_option( 'confirm-user-registration' );
		$user = get_userdata( $user_id );

		$headers = 'FROM:' . $options['from'] . "\r\n";
		$headers = apply_filters( 'confirm-user-registration-notification-header', $headers, $user );
		$subject = apply_filters( 'confirm-user-registration-notification-subject', $options['subject'], $user );
		$message = apply_filters( 'confirm-user-registration-notification-message', $options['message'], $user );

		wp_mail( $user->data->user_email, $subject, $message, $headers );

		do_action( 'send_user_authentication', $user, $headers, $subject, $message );
	}



	/**
	 * Check if user is authed
	 *
	 * @access public
	 * @return bool | WP_Error
	 * @author Ralf Hortt
	 **/
	public function wp_authenticate_user( $user )
	{
		$user = get_user_by( 'login', $user->user_login );

		if ( !is_object( $user ) )
			return FALSE;

		$user_id = $user->ID;

		if ( $this->is_authenticated( $user_id ) ) :
			return $user;
		else :
			$error = new WP_Error();
			$options = get_option( 'confirm-user-registration' );
			$error_message = apply_filters( 'confirm-user-registration-error-message', $options['error'] );
			$error->add( 'error', $error_message );
			return apply_filters( 'confirm-user-registration-error', $error, $user );
		endif;
	}



}
new Confirm_User_Registration;
