<?php
/**
 * Plugin Name: Enable Multi-Site
 * Description: Enables Multi-Site on a Wordpress 3.0 Install.
 * Version: 1.5
 * Revision Date: February 19, 2010
 * Requires at least: WP 3.0
 * Tested up to: WP 3.0.5
 * Author: Jason Grim
 * Author URI: http://jgwebdevelopment.com
 * Plugin URI: http://jgwebdevelopment.com/plugins/wordpress-multi-site-enabler-plugin
 */


// This plugin's version
define ( 'EMS_PLUGIN_VERSION', 1.5 );

class multi_site_enabler_plugin {
	
	// Holds class errors
	private $errors;
	
	/**
	 * Constructor function, gets the whole thing going.
	 * 
	 * @since 1.5.0
	 * @return void
	 */
	public function multi_site_enabler_plugin () {
		// Updates the options table with the current plugin version.
		// This is for possible updates that may need version info later on.
		$current_version = get_option ( 'enable-multi-site-plugin-version', 0 );
		if ( $current_version < EMS_PLUGIN_VERSION )
			update_option ( 'enable-multi-site-plugin-version', EMS_PLUGIN_VERSION );
			
		add_action( 'admin_menu', array ( &$this, 'page_menu' ) );
	}
	
	/**
	 * Creates the blogs.dir directory in the wp-content folder
	 * 
	 * @since 1.4.0
	 * @return bool
	 */
	private function create_blogs_dir () {
		// Check if it's already been created
		if ( $this->check_for_blogs_dir() ) return true;
		
		// Create it if it hasn't been yet
		$did_it_work = @mkdir ( WP_CONTENT_DIR . '/blogs.dir' );
		
		if ( !$did_it_work )
			$this->errors[] = 'WARNING: Could not create folder /wp-content/blogs.dir/. Please create this folder yourself.';
			
		return $did_it_work;
	}
	
	/** 
	 * Check if the wp-content folder is already made for the blogs.dir directory
	 * 
	 * @since 1.4.0
	 * @return bool Whether the folder is already made
	 */
	private function check_for_blogs_dir() {
		if ( is_dir( WP_CONTENT_DIR . '/blogs.dir' ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Check if the htaccss file exist and is editable
	 * 
	 * @since 1.0.0
	 * @return bool Whether the file exist and is writeable
	 */
	private function check_for_htaccess() {
		if ( file_exists( ABSPATH . '.htaccess' ) ) {
			if ( is_writable( ABSPATH . '.htaccess' ) ) {
				return true;
			} else {
				return false;
			}
		} elseif ( is_writable( ABSPATH ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Check if the wp-config.php file exist and is editable
	 * 
	 * @since 1.0.0
	 * @return bool Whether the file exist and is writable
	 */
	private function check_for_wp_config() {
	if ( file_exists( ABSPATH . 'wp-config.php' ) && is_writable( ABSPATH . 'wp-config.php' ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Check for an existing network.
	 *
	 * @since 1.0.0
	 * @return bool Whether a network exists.
	 */
	private function check_existing_network () {
		global $wpdb;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->site'" ) )
			return $wpdb->get_var( "SELECT domain FROM $wpdb->site ORDER BY id ASC LIMIT 1" );
		return false;
	}
	
	/**
	 * Allow subdomain install. Checks to see if it is on a local host.
	 *
	 * @since 1.0.0
	 * @return bool Whether subdomain install is allowed
	 */
	private function allow_subdomain_install () {
		$domain = preg_replace( '|https?://([^/]+)|', '$1', get_option( 'siteurl' ) );
		if ( false !== strpos( $domain, '/' ) || 'localhost' == $domain || preg_match( '|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+|', $domain ) )
			return false;
		return true;
	}
	
	/**
	 * Allow subdirectory install
	 *
	 * @since 1.0.0
	 * @return bool Whether subdirectory install is allowed
	 */
	private function allow_subdirectory_install() {
		global $wpdb;
		if ( apply_filters( 'allow_subdirectory_install', false ) )
			return true;
	
		if ( defined( 'ALLOW_SUBDIRECTORY_INSTALL' ) && ALLOW_SUBDIRECTORY_INSTALL )
			return true;
	
		$post = $wpdb->get_row( "SELECT ID FROM $wpdb->posts WHERE post_date < DATE_SUB(NOW(), INTERVAL 1 MONTH) AND post_status = 'publish'" );
		if ( empty( $post ) )
			return true;
	
		return false;
	}
	
	/**
	 * Get base domain of network.
	 *
	 * @since 1.0.0
	 * @return string Base domain.
	 */
	private function get_clean_basedomain () {
		if ( $existing_domain = $this->check_existing_network() )
			return $existing_domain;
		$domain = preg_replace( '|https?://|', '', get_option( 'siteurl' ) );
		if ( $slash = strpos( $domain, '/' ) )
			$domain = substr( $domain, 0, $slash );
		return $domain;
	}
	
	/**
	 * Creates the htaccess file based on if the site is to use subdomain install or not.
	 * 
	 * @param bool $subdomain_install True to install subdomain install.
	 * @since 1.5.0
	 * @return bool If it worked or not
	 */
	private function create_htaccess_file ( $subdomain_install ) {
		global $base;
		// Creates htaccess file
		$htaccess_file = '#Made Multi-Site with Multi-Site Enabler ' . EMS_PLUGIN_VERSION . '#' . "\n";
		$htaccess_file .= 'RewriteEngine On' . "\n";
		$htaccess_file .= 'RewriteBase ' . $base . "\n";
		$htaccess_file .= 'RewriteRule ^index\.php$ - [L]' . "\n";
		$htaccess_file .= "\n";
		$htaccess_file .= '# uploaded files' . "\n";
		$htaccess_file .= 'RewriteRule ^' . ( $subdomain_install ? '' : '([_0-9a-zA-Z-]+/)?') . 'files/(.+) wp-includes/ms-files.php?file=$' . ( $subdomain_install ? 1 : 2 ) . ' [L]' . "\n";
		
		if ( ! $subdomain_install ) $htaccess_file .= "\n# add a trailing slash to /wp-admin\n" . 'RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]' . "\n";
		
		$htaccess_file .= "\r\n" . 'RewriteCond %{REQUEST_FILENAME} -f [OR]' . "\n";
		$htaccess_file .= 'RewriteCond %{REQUEST_FILENAME} -d' . "\n";
		$htaccess_file .= 'RewriteRule ^ - [L]' . "\n";
		
		if ( ! $subdomain_install ) $htaccess_file .= "\nRewriteRule  ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]\nRewriteRule  ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]";
		
		$htaccess_file .= "\nRewriteRule . index.php [L]";
		
		// Checks if the file can be saved or not
		if ( ! $this->check_for_htaccess () ) return false;
		
		// Saves the file
		$fp = @fopen( ABSPATH . '.htaccess', 'w' );
		fwrite($fp, $htaccess_file);
		fclose($fp);
		
		if ( ! $fp ) return false;		
		return true;
	}
	
	/**
	 * Updates the wp-config.php file with the proper defines.
	 * 
	 * @param bool $subdomain_install True to install subdomain install.
	 * @since 1.5.0
	 * @return bool
	 */
	private function edit_wp_config_file ( $subdomain_install ) {
		global $base;
		
		// Variable to append the beginning of the config file
		$new_contents = "\n\n\n" . '/* This site was made multi-site enabled by @link http://jgwebdevelopment.com */' . "\n";
		
		// Get hostname
		$hostname = $this->get_clean_basedomain ();
		
		// Gets the current config data
		$config_file_contents = file_get_contents ( ABSPATH . 'wp-config.php' );
		
		// Multi-site defines
		$multisite_defines = array (
			'MULTISITE' => true,
			'SUBDOMAIN_INSTALL' => $subdomain_install,
			'DOMAIN_CURRENT_SITE' => $hostname,
			'PATH_CURRENT_SITE' => $base,
			'SITE_ID_CURRENT_SITE' => 1,
			'BLOG_ID_CURRENT_SITE' => 1
		);
		
		// Multi-site variables
		$multisite_variables = array (
			'base' => $base
		);
		
		// Create defines with values.
		foreach ( $multisite_defines as $define => $define_value ) {
			$value = ( is_numeric( $define_value ) ) ? $define_value: "'{$define_value}'";
			$new_contents .= "define ('{$define}', $value);\n";
		}
		
		// Create variables with values.
		foreach ( $multisite_variables as $variable => $variable_value ) {
			$value = ( is_numeric( $variable_value ) ) ? $variable_value: "'{$variable_value}'";
			$new_contents .= '$' . $variable . ' = ' . $value . ';' . "\n";
		}
		
		/* TODO: search the wp-config.php file for current defines to avoid conflict
		 * Due in version 1.6+
		 * 
		$pattern = '/^define\s*\(\s*[\'|"](.+)[\'|"]\s*,\s*[\'|"]?([A-Za-z0-9_]*)[\'|"]?\s*\)\s*;(.*)/i'; // define preg pattern
		
		$matches = preg_match( $pattern, $config_file_contents );
		*/
		
		// TODO: check for the dumb people that use <? instead of <?php
		$pattern = '/<\?php(.*)/i';
		$replace = '<?php' . $new_contents . '\1';
		$updated_config_file_contents = preg_replace ( $pattern, $replace, $config_file_contents, 1 );
		
		// Saves the file
		$fp = @fopen( ABSPATH . 'wp-config.php', 'w' );
		fwrite($fp, $updated_config_file_contents);
		fclose($fp);
		
		if ( ! $fp ) return false;		
		return true;
	}
	
	/**
	 * Checks if there are any errors so far. Also echos out the current errors.
	 * 
	 * @since 1.5.0
	 * @return bool If there are any errors or not
	 */
	private function error_handler () {
		$return = null;
		if ( is_array( $this->errors ) ) {
			foreach ( $this->errors as $error )
				$return .= '<p><strong>' . __('Error:') . '</strong> ' . $error . '</p>';
			echo '<div class="error">';
			echo $return;
			echo '</div>';
		}
		return ! empty ( $this->errors );
	}
	
	/**
	 * Checks the current wordpress configuration to see if multi-site can be installed.
	 * 
	 * @since 1.5.0
	 * @return void
	 */
	private function pre_install_check () {
		global $wpdb;
		// Check if current user is super admin
		if ( ! is_super_admin () )
			$this->errors [] = 'You Are Not Allowed Here! So Get out!';
		
		// Checks if htaccess file is writable
		if ( ! $this->check_for_htaccess () )
			$this->errors [] = 'This plugin will edit the .htaccess file located in the root folder of your Wordpress installation. Please allow write access to this file and/or directory.';
		
		// Checks if config file is writeable
		if ( ! $this->check_for_wp_config () )
			$this->errors [] = 'This plugin will edit the wp-config.php file located in the root folder of your Wordpress installation. Please allow write access to this file and/or directory.';
			
		// Check if the siteurl match the home url
		if ( get_option( 'siteurl' ) != get_option( 'home' ) )
			$this->errors [] = sprintf( __( 'Your <strong>WordPress address</strong> must match your <strong>Site address</strong> before creating a Network. See <a href="%s">General Settings</a>.' ), esc_url( admin_url( 'options-general.php' ) ) );

		// Check if there is a port name in the url
		$hostname = $this->get_clean_basedomain();
		$has_ports = strstr( $hostname, ':' );
		if ( ( false !== $has_ports && ! in_array( $has_ports, array( ':80', ':443' ) ) ) ) {
			$this->errors [] = __( 'You cannot install a network of sites with your server address.' ) . '</p>' .
			'<p>' . sprintf( __( 'You cannot use port numbers such as <code>%s</code>.' ), $has_ports ) . '</p>' .
			'<a href="' . esc_url( admin_url() ) . '">' . __( 'Return to Dashboard' ) . '</a>';
		}
		
		// Checks if there is a custom wp-content page
		if ( WP_CONTENT_DIR != ABSPATH . 'wp-content' )
			$this->errors [] = __( 'Networks may not be fully compatible with custom wp-content directories.' );
	}
	
	/**
	 * Adds the admin menu page.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function page_menu () {
		$settings_page = add_options_page( 'Enable Multi-Site', 'Enable Multi-Site', 'manage_options', 'enable-multi-site', array( &$this, 'admin_settings_page' ) );
	}
	
	/**
	 * Admin menu page.
	 * 
	 * This page changes depending on the current step in setting up multi-site.
	 * 
	 * @since 1.5.0
	 * @return void
	 */
	public function admin_settings_page () {
		global $base, $wpdb;
		// Display Header
		?>
		<div class="wrap">
		
			<h2>Enable Multi-Site</h2>
			
				<?php if ( ! is_multisite () ) : ?>
				<div class="updated inline">
				
					<p>
						<strong>Warning:</strong> This plugin will edit your blogs .htaccess
						file, wp-config.php file, and database structure. Please use at your own
						risk. By using this plugin you agree not to hold the developer
						accountable for any possible damages to your website.
					</p>
					
					<p>
						<strong>Note:</strong> Enabling Multi-Site on Wordpress install has
					some preliminary server-side requirements. Make sure your server
					supports Wordpress Multi-Site's requirements before continuing.
					</p>
					
					<p>
						<a href="http://jgwebdevelopment.com/plugins/wordpress-multi-site-enabler-plugin/wordpress-multi-site-information"
						target="_blank">Shortend version of Multi-Site requirements.</a>
					</p>
					
					<p>
						<a href="http://codex.wordpress.org/Create_A_Network" target="_blank">Wordpress.org
						Codex - Enabling Multi-Site</a>
					</p>
					
					<p>
						Here is a great plugin for backing up your Wordpress Database before
						continuing. <a href="http://wordpress.org/extend/plugins/wp-db-backup/"
						target="_blank">WP-DB-Backup</a>
					</p>
					
				</div>
				<?php endif; ?>
		<?php
		
		// Display any errors
		$there_are_errors = $this->error_handler ();
		
		// Get page name
		$base = trailingslashit ( stripslashes ( dirname ( dirname ( $_SERVER ['SCRIPT_NAME'] ) ) ) );
		
		if ( isset( $_POST ['submit'] ) ) {
		// Do Installation and checking
			require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
			// We need to create references to ms global tables to enable Network.
			foreach ( $wpdb->tables ( 'ms_global' ) as $table => $prefixed_table )
				$wpdb->$table = $prefixed_table;
			
			// Create network tables
			install_network ();
			$hostname = $this->get_clean_basedomain ();
			$subdomain_install = ( ! $this->allow_subdomain_install () ) ? false : ( bool ) $_POST ['subdomain_install'];
			
			if ( ! $this->check_existing_network () ) {
				// Populate network settings
				$result = populate_network ( 1, $this->get_clean_basedomain (), sanitize_email ( $_POST ['email'] ), stripslashes ( $_POST ['sitename'] ), $base, $subdomain_install );
				if ( is_wp_error ( $result ) ) {
					if ( 1 == count ( $result->get_error_codes () ) && 'no_wildcard_dns' == $result->get_error_code () ) $this->enable_multi_site_network_step2 ( $result );
					else $this->enable_multi_site_network_step1 ( $result );
				} else {
					$this->enable_multi_site_network_step2 ();
				}
			} else {
				$this->enable_multi_site_network_step2 ();
			}
		} elseif ( is_multisite () || $this->check_existing_network () ) {
		// Network is already setup
			?>
			<div>
				
				<h3>Multi-Site has been installed on this Wordpress blog.</h3>
				
				<p>
					If you have any questions for feature request or an error to report go to: 
					<a href="http://jgwebdevelopment.com/forum/wordpress-multi-site-enabler" target="_blank">
						JG Web Development Multi-Site Enabler Supprt Forum
					</a>.
				</p>
				
			</div>
			
			<div style="width:450px; border:1px solid #dddddd; background:#fff; padding:20px 20px; float: left; margin: 10px;">
				<h3 style="margin:0; padding:0;">
					Support This Plugin
				</h3>
				<p>
					Don't make me holler, don't make me shout. Turn your pockets inside
					out.. Please donate to this plugin's future below.
				</p>
				<p style="text-align: center;">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input
						type="hidden" name="cmd" value="_s-xclick"> <input type="hidden"
						name="hosted_button_id" value="HKJ6XNRNRTXYA"> <input type="image"
						src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0"
						name="submit" alt="PayPal - The safer, easier way to pay online!"> <img
						alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif"
						width="1" height="1">
					</form>
				</p>
			</div>
			
			<div style="width:450px; border:1px solid #dddddd; background:#fff; padding:20px 20px; float: left; margin: 10px;">
				<!-- ThemeFuse Affiliate box -->
				<h3 style="margin:0; padding:0;">ThemeFuse Original WP Themes</h3>
				<p>
					If you are interested in buying an original wp theme I would recommend 
					<a href="https://www.e-junkie.com/ecom/gb.php?cl=136641&c=ib&aff=157448" target="ejejcsingle">ThemeFuse</a>.
					They make some amazing wp themes, that have a cool 1 click auto install feature and excellent after care support services.
					Check out some of their themes!
				</p>
			    <a style="border:none;" href="https://www.e-junkie.com/ecom/gb.php?cl=136641&c=ib&aff=157448">
			    	<img style="border:none;" src="http://themefuse.com/wp-content/themes/themefuse/images/campaigns/themefuse.jpg" />
			    </a>
				<!-- ThemeFuse Affiliate box END -->
			</div>
			
			<div style="clear: both;"></div>
			<?php
		} else {
		// Pre install check
			$this->pre_install_check ();
			$this->enable_multi_site_network_step1 ();
		}
		
		// Display Footer
		echo '</div>';
		
	}
	
	/**
	 * Prints step 1 for Network installation process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function enable_multi_site_network_step1 ( $errors = false ) {
		global $is_apache;
		$hostname = $this->get_clean_basedomain();	
		echo '<form method="post" action="">';
	
		wp_nonce_field( 'plugin-install-network-1' );
	
		$error_codes = array();
		if ( is_wp_error( $errors ) ) {
			echo '<div class="error"><p><strong>' . __( 'ERROR: The network could not be created.' ) . '</strong></p>';
			foreach ( $errors->get_error_messages() as $error )
				echo "<p>$error</p>";
			echo '</div>';
			$error_codes = $errors->get_error_codes();
		}
	
		$site_name = ( ! empty( $_POST['sitename'] ) && ! in_array( 'empty_sitename', $error_codes ) ) ? $_POST['sitename'] : sprintf( _x('%s Sites', 'Default network name' ), get_option( 'blogname' ) );
		$admin_email = ( ! empty( $_POST['email'] ) && ! in_array( 'invalid_email', $error_codes ) ) ? $_POST['email'] : get_option( 'admin_email' );
		
		?>
		<p><?php _e( 'Welcome to the Network installation process!' ); ?></p>
		<p><?php _e( 'Fill in the information below and you&#8217;ll be on your way to creating a network of WordPress sites. We will create configuration files for you!' ); ?></p>
		<?php
	
		if ( isset( $_POST ['subdomain_install'] ) ) {
			$subdomain_install = (bool) $_POST['subdomain_install'];
		} elseif ( apache_mod_loaded('mod_rewrite') ) { // assume nothing
			$subdomain_install = true;
		} elseif ( ! $this->allow_subdirectory_install() ) {
			$subdomain_install = true;
		} else {
			$subdomain_install = false;
			if ( $got_mod_rewrite = got_mod_rewrite() ) // dangerous assumptions
				echo '<div class="updated inline"><p><strong>' . __( 'Note:' ) . '</strong> ' . __( 'Please make sure the Apache <code>mod_rewrite</code> module is installed as it will be used at the end of this installation.' ) . '</p>';
			elseif ( $is_apache )
				echo '<div class="error inline"><p><strong>' . __( 'Warning!' ) . '</strong> ' . __( 'It looks like the Apache <code>mod_rewrite</code> module is not installed.' ) . '</p>';
			if ( $got_mod_rewrite || $is_apache ) // Protect against mod_rewrite mimicry (but ! Apache)
				echo '<p>' . __( 'If <code>mod_rewrite</code> is disabled, ask your administrator to enable that module, or look at the <a href="http://httpd.apache.org/docs/mod/mod_rewrite.html">Apache documentation</a> or <a href="http://www.google.com/search?q=apache+mod_rewrite">elsewhere</a> for help setting it up.' ) . '</p></div>';
		}
	
		?>
			<h3><?php esc_html_e( 'Addresses of Sites in your Network' ); ?></h3>
			<p><?php _e( 'Please choose whether you would like sites in your WordPress network to use sub-domains or sub-directories. <strong>You cannot change this later.</strong>' ); ?></p>
			<p><?php _e( 'You will need a wildcard DNS record if you are going to use the virtual host (sub-domain) functionality.' ); ?></p>
			<?php // @todo: Link to an MS readme? ?>
			<table class="form-table">
				<tr>
					<th><label><input type='radio' name='subdomain_install' value='1'<?php checked( $subdomain_install ); ?> /> <?php _e( 'Sub-domains' ); ?></label></th>
					<td><?php printf( _x( 'like <code>site1.%1$s</code> and <code>site2.%1$s</code>', 'subdomain examples' ), $hostname ); ?></td>
				</tr>
				<tr>
					<th><label><input type='radio' name='subdomain_install' value='0'<?php checked( ! $subdomain_install ); ?> /> <?php _e( 'Sub-directories' ); ?></label></th>
					<td><?php printf( _x( 'like <code>%1$s/site1</code> and <code>%1$s/site2</code>', 'subdirectory examples' ), $hostname ); ?></td>
				</tr>
			</table>
	
		<?php
			$is_www = ( 0 === strpos( $hostname, 'www.' ) );
			if ( $is_www ) :
			?>
			<h3><?php esc_html_e( 'Server Address' ); ?></h3>
			<p><?php printf( __( 'We recommend you change your siteurl to <code>%1$s</code> before enabling the network feature. It will still be possible to visit your site using the <code>www</code> prefix with an address like <code>%2$s</code> but any links will not have the <code>www</code> prefix.' ), substr( $hostname, 4 ), $hostname ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope='row'><?php esc_html_e( 'Server Address' ); ?></th>
					<td>
						<?php printf( __( 'The internet address of your network will be <code>%s</code>.' ), $hostname ); ?>
					</td>
				</tr>
			</table>
			<?php endif; ?>
	
			<h3><?php esc_html_e( 'Network Details' ); ?></h3>
			<table class="form-table">
			<?php if ( 'localhost' == $hostname ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sub-directory Install' ); ?></th>
					<td><?php
						_e( 'Because you are using <code>localhost</code>, the sites in your WordPress network must use sub-directories. Consider using <code>localhost.localdomain</code> if you wish to use sub-domains.' );
						// Uh oh:
						if ( ! $this->allow_subdirectory_install() )
							echo ' <strong>' . __( 'Warning!' ) . ' ' . __( 'The main site in a sub-directory install will need to use a modified permalink structure, potentially breaking existing links.' ) . '</strong>';
					?></td>
				</tr>
			<?php elseif ( ! $this->allow_subdomain_install() ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sub-directory Install' ); ?></th>
					<td><?php
						_e( 'Because your install is in a directory, the sites in your WordPress network must use sub-directories.' );
						// Uh oh:
						if ( ! $this->allow_subdirectory_install() )
							echo ' <strong>' . __( 'Warning!' ) . ' ' . __( 'The main site in a sub-directory install will need to use a modified permalink structure, potentially breaking existing links.' ) . '</strong>';
					?></td>
				</tr>
			<?php endif; ?>
			
			<?php if ( ! $this->allow_subdirectory_install() ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sub-domain Install' ); ?></th>
					<td><?php _e( 'Because your install is not new, the sites in your WordPress network should use sub-domains.' );
						echo ' <strong>' . __( 'The main site in a sub-directory install will need to use a modified permalink structure, potentially breaking existing links.' ) . '</strong>';
					?></td>
				</tr>
			<?php endif; ?>
			
			<?php if ( ! $is_www ) : ?>
				<tr>
					<th scope='row'><?php esc_html_e( 'Server Address' ); ?></th>
					<td>
						<?php printf( __( 'The internet address of your network will be <code>%s</code>.' ), $hostname ); ?>
					</td>
				</tr>
			<?php endif; ?>
				<tr>
					<th scope='row'><?php esc_html_e( 'Network Title' ); ?></th>
					<td>
						<input name='sitename' type='text' size='45' value='<?php echo esc_attr( $site_name ); ?>' />
						<br /><?php _e( 'What would you like to call your network?' ); ?>
					</td>
				</tr>
				<tr>
					<th scope='row'><?php esc_html_e( 'Admin E-mail Address' ); ?></th>
					<td>
						<input name='email' type='text' size='45' value='<?php echo esc_attr( $admin_email ); ?>' />
						<br /><?php _e( 'Your email address.' ); ?>
					</td>
				</tr>
			</table>
			<p class='submit'><input class="button-primary" name='submit' type='submit' value='<?php esc_attr_e( 'Install' ); ?>' /></p>
		</form>
			<?php
	}
	
	/**
	 * Prints step 2 for Network installation process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function enable_multi_site_network_step2 ( $errors = false ) {
		global $wpdb;
		// Wildcard DNS message.
		if ( is_wp_error ( $errors ) ) echo '<div class="error">' . $errors->get_error_message () . '</div>';
		
		if ( $_POST ) 
			$subdomain_install = ( ! empty ( $_POST ['subdomain_install'] ) && $_POST ['subdomain_install'] == 1 ) ? true: false;
		else {
			if ( is_multisite () )
				$subdomain_install = is_subdomain_install ();
			else
				$subdomain_install = ( bool ) $wpdb->get_var ( "SELECT meta_value FROM $wpdb->sitemeta WHERE site_id = 1 AND meta_key = 'subdomain_install'" );
		}
		
		if ( $_POST || ! is_multisite () ) {
			// Creates config file additions
			$this->edit_wp_config_file( $subdomain_install );
		
			// Create htaccess file
			$this->create_htaccess_file( $subdomain_install );
			
			// Creates blogs.dir directory	
			$this->create_blogs_dir();
		}
		
		// Display any errors
		$there_are_errors = $this->error_handler ();
		
		// Sends out success message.
		echo '<h3>You will now need to <a href="' . get_bloginfo('url') . '/wp-login.php">login again.</a></h3>';
	}
	
} $multi_site_enabler_plugin = new multi_site_enabler_plugin ();