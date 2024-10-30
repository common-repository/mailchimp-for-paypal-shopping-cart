<?php
/*
Plugin Name: MailChimp for Paypal Shopping Cart
Plugin URI: https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768
Description: MailChimp for Paypal plugin allows you to add subscribers to your MailChimp newsletter list after a customer purchase items from your site.
Author: Tips and Tricks HQ
Version: 1.1
Author URI: https://www.tipsandtricks-hq.com/
License: GPLv2 or later
*/

/*
** Function for adding menu and submenu
*/
if ( ! function_exists( 'mlchpfrppl_admin_menu' ) ) {
	function mlchpfrppl_admin_menu() {
		add_submenu_page( 'options-general.php',  __( 'MailChimp Settings', 'mailchimp-for-paypal' ),  __( 'MailChimp Settings', 'mailchimp-for-paypal' ), 'manage_options', 'mlchpfrppl_settings', 'mlchpfrppl_settings_page' );
	}
}

/*
* Function to add actions link to block with plugins name on "Plugins" page 
*/
if ( ! function_exists( 'mlchpfrppl_plugin_action_links' ) ) {
	function mlchpfrppl_plugin_action_links( $links, $file ) {
		static $this_plugin;
		if ( ! $this_plugin ) 
			$this_plugin = plugin_basename( __FILE__ );
		if ( $file == $this_plugin ) {
				$settings_link = '<a href="options-general.php?page=mlchpfrppl_settings">' . __( 'Settings', 'mailchimp-for-paypal' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		return $links;
	}
}

/*
* Function to add links to description block on "Plugins" page 
*/
if ( ! function_exists( 'mlchpfrppl_register_plugin_links' ) ) {
	function mlchpfrppl_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			$links[] = '<a href="admin.php?page=cntctfrmtdb_settings">' . __( 'Settings','mailchimp-for-paypal' ) . '</a>';
		}
		return $links;
	}
}

/* Activation plugin function */
if ( ! function_exists( 'mlchpfrppl_plugin_activate' ) ) {
	function mlchpfrppl_plugin_activate( $networkwide ) {
		global $wpdb;
		/* Activation function for network */
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			/* check if it is a network activation - if so, run the activation function for each blog id */
			if ( $networkwide ) {
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					mlchpfrppl_create_table();
				}
				switch_to_blog( $old_blog );
				return;
			}
		}
		mlchpfrppl_create_table();
	}
}

/*
** Function add table for database.
*/
if ( ! function_exists( 'mlchpfrppl_create_table' ) ) {
	function mlchpfrppl_create_table() {
		global $wpdb, $prefix;
		$prefix = $wpdb->prefix . 'mlchpfrppl_';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$sql = "CREATE TABLE IF NOT EXISTS `" . $prefix . "mailchimp_settings` (
			`id` INT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
			`api_key` varchar(64) NOT NULL,
			`id_list` varchar(64) NOT NULL,
			`double_optin` varchar(64) NOT NULL,
			`update_existing` varchar(64) NOT NULL,
			`replace_interests` varchar(64) NOT NULL,
			`send_welcome` varchar(64) NOT NULL,
			PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		$wpdb->insert( $prefix . 'mailchimp_settings', array(
				'api_key'			=> "",
				'id_list'			=> "",
				'double_optin'		=> "true",
				'update_existing'	=> "true",
				'replace_interests'	=> "true",
				'send_welcome'		=> "false"
			));
	}
}

/*
** Function for displaying settings page of plugin.
*/
if ( ! function_exists( 'mlchpfrppl_settings_page' ) ) {
	function mlchpfrppl_settings_page() {
		global $wpdb, $prefix, $options; 
		$prefix = $wpdb->prefix . 'mlchpfrppl_';
		// set value of input type="hidden" when options is changed
		if( isset( $_POST['api_key'] ) && isset( $_POST['id_list'] ) ) {
			$wpdb->update( $prefix . 'mailchimp_settings', array(
				'api_key'			=> isset( $_POST['api_key'] ) ? $_POST['api_key'] : "",
				'id_list'			=> isset( $_POST['id_list'] ) ? $_POST['id_list'] : "",
				'double_optin'		=> isset( $_POST['double_optin'] ) ? $_POST['double_optin'] : "true",
				'update_existing'	=> isset( $_POST['update_existing'] ) ? $_POST['update_existing'] : "true",
				'replace_interests'	=> isset( $_POST['replace_interests'] ) ? $_POST['replace_interests'] : "true",
				'send_welcome'		=> isset( $_POST['send_welcome'] ) ? $_POST['send_welcome'] : "true"
			),
			array( 'ID' => 1 ),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}
		$options = $wpdb->get_results( "SELECT * FROM `" . $prefix . "mailchimp_settings` WHERE id = '1' ");
		foreach ($options as $key => $value) { 
			$options[$key] = $value; ?>
			<h2><?php _e( "MailChimp Settings", 'mailchimp-for-paypal' ); ?></h2>
			<div class="wrap">
				<form id="mlchpfrppl_settings_form" method="post" action="options-general.php?page=mlchpfrppl_settings">
					<div class="mailchimp-content">
						<div id="mlchpfrppl_settings_notice" class="updated fade" style="display:none">
							<p>
								<strong><?php _e( "Notice:", 'mailchimp-for-paypal' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'mailchimp-for-paypal' ); ?>
							</p>
						</div>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th>
										<?php _e( "API Key", 'mailchimp-for-paypal' ); ?>:
									</th>
									<td>
										<input id="mailchimp-app-id" type="text" size="40" maxlength="42" value="<?php if ( isset( $options[$key]->api_key ) ){ echo $options[$key]->api_key; } ?>" name="api_key">
										<p class="description"><?php _e( "Your MailChimp API key", 'mailchimp-for-paypal' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th>
										<?php _e( "List ID", 'mailchimp-for-paypal' ); ?>:
									</th>
									<td>
										<input id="mailchimp-app-secret" type="text" size="40" value="<?php if ( isset( $options[$key]->id_list ) ){ echo $options[$key]->id_list; } ?>" name="id_list">
										<p class="description"><?php _e( "Your MailChimp List ID", 'mailchimp-for-paypal' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th>
										<?php _e( "Double Opt-In", 'mailchimp-for-paypal' ); ?>:
									</th>
									<td>
										<select name="double_optin">
											<option <?php if ( '' == $options[$key]->double_optin  ) echo "selected=\"selected\" "; ?>><?php _e( "Select an Option", 'mailchimp-for-paypal' ); ?></option>
											<option value="true" <?php if ( "true" == $options[$key]->double_optin  ) echo "selected=\"selected\" "; ?>>Yes</option>
											<option value="false" <?php if ( "false" == $options[$key]->double_optin  ) echo "selected=\"selected\" "; ?>>No</option>
										</select>
										<p class="description"><?php _e( "Require Double Opt-In confirmation (defaults to Yes). Abusing this may cause your Mailchimp account to be suspended.", 'mailchimp-for-paypal' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th>
										<?php _e( "Update Existing", 'mailchimp-for-paypal' ); ?>:
									</th>
									<td>
										<select name="update_existing">
											<option <?php if ( '' == $options[$key]->update_existing  ) echo "selected=\"selected\" "; ?>><?php _e( "Select an Option", 'mailchimp-for-paypal' ); ?></option>
											<option value="true" <?php if ( "true" == $options[$key]->update_existing  ) echo "selected=\"selected\" "; ?>>Yes</option>
											<option value="false" <?php if ( "false" == $options[$key]->update_existing  ) echo "selected=\"selected\" "; ?>>No</option>
										</select>
										<p class="description"><?php _e( "Update existing subscribers instead of throwing an error (defaults to Yes).", 'mailchimp-for-paypal' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th>
										<?php _e( "Replace Interests", 'mailchimp-for-paypal' ); ?>:
									</th>
									<td>
										<select name="replace_interests">
											<option <?php if ( '' == $options[$key]->replace_interests  ) echo "selected=\"selected\" "; ?>><?php _e( "Select an Option", 'mailchimp-for-paypal' ); ?></option>
											<option value="true" <?php if ( "true" == $options[$key]->replace_interests  ) echo "selected=\"selected\" "; ?>>Yes</option>
											<option value="false" <?php if ( "false" == $options[$key]->replace_interests  ) echo "selected=\"selected\" "; ?>>No</option>
										</select>
										<p class="description"><?php _e( "Replace the member's interest groups with those provided, instead of adding the provided groups to the member's existing interest groups (defaults to Yes)", 'mailchimp-for-paypal' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th>
										<?php _e( "Send Welcome", 'mailchimp-for-paypal' ); ?>:
									</th>
									<td>
										<select name="send_welcome">
											<option <?php if ( '' == $options[$key]->send_welcome  ) echo "selected=\"selected\" "; ?>><?php _e( "Select an Option", 'mailchimp-for-paypal' ); ?></option>
											<option value="true" <?php if ( "true" == $options[$key]->send_welcome  ) echo "selected=\"selected\" "; ?>>Yes</option>
											<option value="false" <?php if ( "false" == $options[$key]->send_welcome  ) echo "selected=\"selected\" "; ?>>No</option>
										</select>
										<p class="description"><?php _e( "Send your list's Welcome Email if the subscription succeeds (except when updating an existing subscriber). <strong>Welcome messages will only be sent if Double Opt-In is turned off</strong>. Defaults to No.", 'mailchimp-for-paypal' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table><!-- .form-table -->
					</div><!-- .mailchimp-content -->
					<input type="submit" id="submit_options" class="button-primary" value="<?php _e( 'Save all changes', 'mailchimp-for-paypal'  ); ?>" />
				</form><!-- #mlchpfrppl_settings_form -->	
				<br class="clear">	
			</div><!-- .wrap -->
		<?php }
	}
}
/*
** Function to transfer data from Paypal in mailchimp
*/
if ( ! function_exists ( 'wspsc_do_mailchimp_signup' ) ) {
	function wspsc_do_mailchimp_signup($ipn_data) {
	    if (isset( $ipn_data['first_name'] ) && isset( $ipn_data['last_name'] ) && isset( $ipn_data['payer_email'] ) ) {
			global $wpdb, $prefix, $options; 
			$prefix = $wpdb->prefix . 'mlchpfrppl_';
			require_once( 'mailchimp.php' );	
			$options = $wpdb->get_results( "SELECT * FROM `" . $prefix . "mailchimp_settings` WHERE id = '1' ");
			foreach ($options as $key => $value) { 
				$MailChimp = new \Drewm\MailChimp( $options[$key]->api_key );
				$result = $MailChimp->call('lists/subscribe', array(
					'id'                => $options[$key]->id_list,
					'email'             => array( 'email'=>$ipn_data['payer_email'] ),
					'merge_vars'        => array( 'FNAME'=>$ipn_data['first_name'], 'LNAME'=>$ipn_data['last_name'] ),
					'double_optin'      => $options[$key]->double_optin,
					'update_existing'   => $options[$key]->update_existing,
					'replace_interests' => $options[$key]->replace_interests,
					'send_welcome'      => $options[$key]->send_welcome,
				));
			}
		}
	}
}
/*
** Function to add stylesheets and scripts for admin bar 
*/
if ( ! function_exists ( 'mlchpfrppl_admin_head' ) ) {
	function mlchpfrppl_admin_head() {
		/* Call register settings function */
		$mlchpfrppl_pages = 'mlchpfrppl_manager';
		if ( 'mlchpfrppl_manager' == isset( $_REQUEST['page'] ) ){
			global $wp_version;
			if ( $wp_version < 3.8 )
				wp_enqueue_style( 'mlchpfrppl_stylesheet', plugins_url( '/css/style_wp_before_3.8.css', __FILE__ ) );	
			else
				wp_enqueue_style( 'mlchpfrppl_stylesheet', plugins_url( '/css/style.css', __FILE__ ) );
		}
		wp_enqueue_script( 'mlchpfrppl_script', plugins_url( 'js/script.js', __FILE__ ) ); ?>
	<?php }
}

/* 
** Function for delete options.
*/
if ( ! function_exists ( 'mlchpfrppl_delete_options' ) ) {
	function mlchpfrppl_delete_options() {
		global $wpdb, $prefix;
		$prefix = $wpdb->prefix . 'mlchpfrppl_';
		$sql = "DROP TABLE `" . $prefix . "mailchimp_settings`;" ;
		$wpdb->query( $sql );
	}
}




/* Activate plugin */
register_activation_hook( __FILE__, 'mlchpfrppl_plugin_activate' );
/* add menu items in to dashboard menu */
add_action( 'admin_menu', 'mlchpfrppl_admin_menu' );
/*add hook to transfer data from Paypal in mailchimp*/
add_action('wpspc_paypal_ipn_processed', 'wspsc_do_mailchimp_signup');
/*add pligin scripts and stylesheets*/
add_action( 'admin_enqueue_scripts', 'mlchpfrppl_admin_head' );
/* add action link of plugin on "Plugins" page */
add_filter( 'plugin_action_links', 'mlchpfrppl_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'mlchpfrppl_register_plugin_links', 10, 2 );
/* uninstal hook */
register_uninstall_hook( __FILE__, 'mlchpfrppl_delete_options' );

?>