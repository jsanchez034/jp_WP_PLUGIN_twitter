<?php
/*
Plugin Name: Twitter Widget with Cache
Plugin URI: http://example.com/wordpress-plugins/my-plugin
Description: Simple Twitter Widget with OAuth and Caching
Version: 1.0 
Author: Jon-Pierre Sanchez 
Author URI: http://example.com
*/

require_once dirname( __FILE__ ) . '/OAuth/tmhOAuth.php';
require_once dirname( __FILE__ ) . '/OAuth/tmhUtilities.php';
require_once dirname( __FILE__ ) . '/edcrypt.php';
require_once dirname( __FILE__ ) . '/widget.php';

register_activation_hook(__FILE__,'jpst_install');
function jpst_install() {
	//Create OAuth key/token options
	$jpst_option_arr = array(
			"jpst_consumer_key"=>'',
			"jpst_consumer_secret"=>'',
			"jpst_user_token"=>'',
			"jpst_user_secret"=>''
			);

	update_option('jpst_oauth', $jpst_option_arr);
}

register_deactivation_hook(__FILE__,'jpst_uninstall');
function jpst_uninstall() {
	delete_option('jpst_oauth');
}

add_action('init', 'jpst_init');
function jpst_init() {
	//Load localization
	load_plugin_textdomain('jpst-plugin', false, plugin_basename(dirname(__FILE__).'/localization'));
	
	// maintain pre-2.6 compatibility 
	if ( !defined( 'WP_CONTENT_URL' ) )
		define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
	if ( !defined( 'WP_CONTENT_DIR' ) )
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ( !defined( 'WP_PLUGIN_URL' ) )
		define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
	if ( !defined( 'WP_PLUGIN_DIR' ) )
		define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	if ( !defined( 'WP_LANG_DIR') )
		define( 'WP_LANG_DIR', WP_CONTENT_DIR . '/languages' );
		
	
}

add_action('admin_menu', 'jpst_create_menu');
function jpst_create_menu() {
//create new top-level menu 
add_menu_page('Twitter Widget with Cache', 'Twitter Widget','administrator', __FILE__, 'jpst_settings_page',plugins_url('/twitterbrd.png', __FILE__));

//call register settings function 
add_action( 'admin_init', 'jpst_register_settings' );

} 

function jpst_register_settings() {
	//register our settings with callback that encrypts data
	register_setting( 'jpst-settings-group', 'jpst_oauth','saveOptionCallback' );
}

			/*echo $options["jpst_consumer_key"] . "<br/>";
			echo $options["jpst_consumer_secret"] . "<br/>";
			echo decrypt($options["jpst_user_token"]) . "<br/>";
			echo decrypt($options["jpst_user_secret"]) . "<br/>";*/

function jpst_settings_page(){
$options = get_option('jpst_oauth');
?>

<div class="wrap"> 
<h2><?php _e('Twitter Widget with Cache Options', 'jpst-plugin') ?></h2>
		<p><?php _e('Please go to', 'jpst-plugin')?> <a href="dev.twitter.com/apps/new">dev.twitter.com/apps/new</a> <?php _e(', fill out the form and copy and paste credentials below...', 'jpst-plugin')?> </p>
		<form method="post" action="options.php"> 
			<?php settings_fields( 'jpst-settings-group' ); ?>
			<table class="form-table">
				<tr valign="top"> 
					<th scope="row"><?php _e('Consumer Key', 'jpst-plugin') ?></th>
					<td><input type="text" name="jpst_oauth[jpst_consumer_key]" 
					value="<?php echoVals($options["jpst_consumer_key"]); ?>" /></td>
				</tr>
				<tr valign="top"> 
					<th scope="row"><?php _e('Consumer Secret', 'jpst-plugin') ?></th>
					<td><input type="text" name="jpst_oauth[jpst_consumer_secret]" 
					value="<?php echoVals($options["jpst_consumer_secret"]); ?>" /></td>
				</tr>
					<tr valign="top"> 
					<th scope="row"><?php _e('User Token', 'jpst-plugin') ?></th>
					<td><input type="text" name="jpst_oauth[jpst_user_token]" 
					value="<?php echoVals($options["jpst_user_token"]); ?>" /></td>
				</tr>
				</tr>
					<tr valign="top"> 
					<th scope="row"><?php _e('User Secret', 'jpst-plugin') ?></th>
					<td><input type="text" name="jpst_oauth[jpst_user_secret]" value="<?php echoVals($options["jpst_user_secret"]); ?>" /></td>
				</tr>
			</table>
			<p class="submit"> 
			<input type="submit" class="button-primary"	value="<?php _e('Save Changes', 'jpst-plugin') ?>" />
			</p>
		</form>
</div>


<?php
}

function saveOptionCallback($arr) {
	foreach ($arr as &$opv) {
		$opv = encrypt($opv);
	}
	return $arr;
}

function echoVals($val) {
	if (!empty($val)) {
		echo decrypt($val);
	}
}

?>