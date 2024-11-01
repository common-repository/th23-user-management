<?php
/*
th23 User Management
Admin area

Copyright 2010-2019, Thorsten Hartmann (th23)
http://th23.net
*/

class th23_user_management_admin extends th23_user_management_pro {

	function __construct() {

		parent::__construct();

		// Setup basic variables (additions for backend)
		$this->requirements = $this->check_requirements();
		$this->settings_base = 'options-general.php';
		$this->settings_base_url = $this->settings_base . '?page=' . $this->slug;
		$this->settings_permission = 'manage_options';
		$this->support_url = 'http://th23.net/th23-user-management-faq-and-support';

		// Retrieve plugin data
		add_action('admin_menu', array(&$this, 'plugin_data'));

		// Install/ uninstall
		add_action('activate_' . $this->base_name, array(&$this, 'install'));
		add_action('deactivate_' .$this->base_name, array(&$this, 'uninstall'));

		// Modify plugin overview page
		add_filter('plugin_action_links', array(&$this, 'settings_link'), 10, 2);
		add_filter('plugin_row_meta', array(&$this, 'contact_link'), 10, 2);

		// Add option to specify title and URL for a legal information page in general admin
		add_action('admin_init', array(&$this, 'add_general_options'));

		// Add admin page and JS/ CSS
		add_action('admin_init', array(&$this, 'register_admin_js_css'));
		add_action('admin_menu', array(&$this, 'add_admin'));

		// If the user management page "dummy" is deleted, trashed or un-published create a new one
		add_action('after_delete_post', array(&$this, 'delete_user_management_page'));
		add_action('transition_post_status', array(&$this, 'unpublish_user_management_page'), 10, 3);

		// User role "pending" should not be choosable as "New User Default Role" - which will be assigned to users after admin approval, if required
		add_filter('editable_roles', array(&$this, 'hide_user_role_pending'));

		// Enhance user page by adding colums showing dates "Registered", "Last Login", "Last Visit"
		add_filter('manage_users_columns', array($this,'users_columns'));
		add_action('manage_users_custom_column',  array($this ,'users_custom_columns'), 10, 3);
		add_filter('manage_users_sortable_columns', array($this ,'users_sortable_columns'));
		add_filter('users_list_table_query_args', array($this ,'users_orderby_column'));
	}

	// Ensure PHP <5 compatibility
	function th23_user_management_admin() {
		self::__construct();
	}

	// Check requirements
	function check_requirements() {
		if(is_multisite()) {
			return false;
		}
		return true;
	}

	// Load plugin data for admin
	function plugin_data() {
		$this->plugin_data = get_plugin_data($this->file);
	}

	// Install
	function install() {
		add_role($this->plugin . '_pending', __('Pending', 'th23-user-management'), array('read' => true));
		update_option($this->plugin . '_options', $this->get_options(array_merge($this->options, $this->create_user_management_page())));
		$this->options = get_option($this->plugin . '_options');
		// Initiate last login / last visit information for existing users, to ensure they are kept in while sorting on user screen
		$users = get_users();
		foreach($users as $user) {
			$this->initiate_last_login_visit($user->ID);
		}
	}

	// Create "dummy" post that will be used to show the user management page
	function create_user_management_page() {
		global $current_user, $wpdb;
		$admin_id = 0;
		if(isset($current_user->roles) && in_array('administrator', $current_user->roles) && isset($current_user->ID)) {
			$admin_id = $current_user->ID;
		}
		else {
			$admin = $wpdb->get_row('SELECT ' . $wpdb->users . '.ID FROM ' . $wpdb->users . ' WHERE (SELECT ' . $wpdb->usermeta . '.meta_value FROM ' . $wpdb->usermeta . ' WHERE ' . $wpdb->usermeta . '.user_id = ' . $wpdb->users . '.ID AND ' . $wpdb->usermeta . '.meta_key = "wp_capabilities") LIKE "%administrator%" LIMIT 1;');
			if(isset($admin->ID)) {
				$admin_id = $admin->ID;
			}
		}
		$user_management_page_details = array(
			'post_title' => __('User Management', 'th23-user-management'),
			'post_name' => 'user-management',
			'post_content' => '<h2><strong>' . __('You can NOT delete this page!', 'th23-user-management') . '</strong></h2><p>' . __('This page belongs to the th23 User Management plugin and is required by it to work properly!', 'th23-user-management') . '</p><p>' . __('Do NOT worry...your visitors on the site will never see this text - it will be replaced by the appropriate page, e.g. the registration page, the edit profile page, etc.', 'th23-user-management') . '</p><p><span style="color: #c0c0c0;">Copyright 2010-2019, Thorsten Hartmann (th23)</span></p>',
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_author' => $admin_id,
			'comment_status' => 'closed'
		);
		return array('page_id' => wp_insert_post($user_management_page_details));
	}

	// If the user management page "dummy" is deleted, trashed or un-published create a new one
	function delete_user_management_page($item_id) {
		if(!empty($this->options['page_id']) && $item_id == $this->options['page_id']) {
			$this->install();
		}
	}
	function unpublish_user_management_page($new_status, $old_status, $post) {
		if(!empty($this->options['page_id']) && $post->ID == $this->options['page_id'] && $new_status != 'publish') {
			wp_delete_post($this->options['page_id'], true);
		}
	}

	// User role "Pending" should not be choosable as "New User Default Role" on "General Settings" and plugin settings page
	// Note: "Pending" role will be assigned to users missing mail validation or admin approval!
	function hide_user_role_pending($roles) {
		unset($roles[$this->plugin . '_pending']);
		return $roles;
	}

	// Uninstall
	function uninstall() {
		// NOTICE: To keep all settings etc in case user wants to reactivate and not to start from scratch following lines are commented out!
		// delete_option($this->plugin . '_options');
		// NOTICE: We keep this role - otherwise unapproved/unvalidated users have no role assigned and will have access, role will become visible normally in admin area
		// remove_role($this->plugin . '_pending');
		remove_action('after_delete_post', array(&$this, 'delete_user_management_page'));
		wp_delete_post($this->options['page_id'], true);
	}

	// Add settings link to plugin actions in plugin overview page
	function settings_link($links, $file) {
		if(plugin_basename($this->file) !== $file) {
			return $links;
		}
		return array_merge(array('<a href="' . $this->settings_base_url . '">' . __('Settings', 'th23-user-management') . '</a>'), $links);
	}

	// Add supporting information, links and notices to plugin row in plugin overview page
	// Note: CSS styling needs to be "hardcoded" here as plugin CSS might not be loaded (e.g. when plugin deactivated) - and standard WordPress classes trigger repositioning of notices
	function contact_link($links, $file) {
		if(plugin_basename($this->file) !== $file) {
			return $links;
		}
		// Enhance version number with edition details
		if(!empty($this->pro)) {
			$links[0] = $links[0] . ' <span class="' . $this->slug . '-admin-professional" style="font-weight: bold; font-style: italic; color: #336600;">Professional</span>';
			$links[] = '<a href="' . esc_url($this->plugin_data['PluginURI'] . '-faq-and-support') . '">' . __('Support', 'th23-user-management') . '</a>';
		}
		else {
			$upgrade = '';
			if($this->requirements) {
				$upgrade .= ' - <a href="' . esc_url($this->plugin_data['PluginURI']) . '" title="' . __('Get additional functionality', 'th23-user-management') . '" class="' . $this->slug . '-admin-get-professional" style="color: #CC3333; font-weight: bold;">';
				/* translators: parses in "Professional" as name of the version */
				$upgrade .= sprintf(__('Upgrade to %s version', 'th23-user-management'), '<i>Professional</i>');
				$upgrade .= '</a>';
			}
			$links[0] = $links[0] . ' <span class="' . $this->slug . '-admin-basic" style="font-style: italic;">Basic</span>' . $upgrade;
			$links[] = '<a href="https://wordpress.org/support/plugin/' . $this->slug . '/">' . __('Support', 'th23-user-management') . '</a>';
		}
		// Check plugin requirements - show warning, if requirements are not met
		$notice = '';
		if(!$this->requirements) {
			$notice .= '<div style="margin: 1em 0; padding: 5px 10px; background-color: #FFFFFF; border-left: 4px solid #FFBA00; box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);"><strong>' . __('Warning', 'th23-user-management') . '</strong>: ' . sprintf(__('This plugin might not work properly on your installation - please check <a href="%s">Settings page</a> for details', 'th23-user-management'), $this->settings_base_url) . '</div>';
		}
		// Check PRO file is matching version to rest of plugin
		if(!empty($this->pro) && $this->pro_version != $this->plugin_data['Version']) {
			$notice .= '<div style="margin: 1em 0; padding: 5px 10px; background-color: #FFFFFF; border-left: 4px solid #DD3D36; box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);"><strong>' . __('Error', 'th23-user-management') . '</strong>: ';
			/* translators: 1: "Professional" as name of the version, 2: "th23-user-management-pro.php" as file name, 3: version number of the PRO file, 4: version number of main file, 5: link to WP update page, 6: link to th23.net plugin download page */
			$notice .= sprintf(__('The version of the %1$s file (%2$s, version %3$s) does not match with the overall plugin (version %4$s) - please make sure you update the overall plugin to the latest version e.g. via the <a href="%5$s">automatic update function</a> and upload the latest version of the %1$s file from %6$s onto your webserver', 'th23-user-management'), '<strong><i>Professional</i></strong>', '<code>' . $this->slug . '-pro.php</code>', $this->pro_version, $this->plugin_data['Version'], 'update-core.php', '<a href="' . esc_url($this->plugin_data['PluginURI']) . '">th23.net</a>');
			$notice .= '</div>';
		}
		// Add notices to/ after last link
		$last = array_pop($links);
		$links[] = $last . $notice;
		return $links;
	}

	// Add option to specify title and URL for a legal information page in general admin
	// Note: This options can be defined by other th23 themes and plugins as well
	function add_general_options() {
		add_settings_section('th23_terms', '<a name="th23_terms"></a>' . __('Legal information', 'th23-user-management') . '<!-- th23 User Management -->', array($this, 'admin_general_section_description'), 'general');
		register_setting('general', 'th23_terms_title');
		add_settings_field(
			'th23_terms_title',
			__('Title', 'th23-user-management'),
			array($this, 'admin_general_show_field'),
			'general',
			'th23_terms',
			array(
				'id' => 'th23_terms_title',
				'description' => __('If left empty, &quot;Terms of Usage&quot; will be used', 'th23-user-management')
			)
		);
		register_setting('general', 'th23_terms_url');
		add_settings_field(
			'th23_terms_url',
			__('URL', 'th23-user-management'),
			array($this, 'admin_general_show_field'),
			'general',
			'th23_terms',
			array(
				'id' => 'th23_terms_url',
				'description' => __('Can be relative URL - if left empty, no link will be added', 'th23-user-management'),
				'input_class' => 'regular-text code'
			)
		);
	}

	// Show description for section in general admin
	function admin_general_section_description() {
		echo '<p>' . __('Reference a page providing user with legally required information about terms of usage, impressum and data provacy policy', 'th23-user-management') . '</p>';
	}

	// Show option settings fields in general admin
	function admin_general_show_field($args) {
		$class = (isset($args['input_class'])) ? $args['input_class'] : 'regular-text';
		echo '<input class="' . $class . '" type="text" id="'. $args['id'] .'" name="'. $args['id'] .'" value="' . get_option($args['id']) . '" />';
		if(isset($args['description'])) {
			echo '<p class="description">' . esc_html($args['description']) . '</p>';
		}
	}

	// Register admin JS and CSS
	function register_admin_js_css() {
		wp_register_script($this->slug . '-admin-js', plugins_url('/' . $this->slug . '-admin.js', $this->file), array('jquery'), $this->version, true);
		wp_register_style($this->slug . '-admin-css', plugins_url('/' . $this->slug . '-admin.css', $this->file), array(), $this->version);
	}

	// Register admin page in admin menu/ prepare loading admin JS and CSS
	function add_admin() {
		$page = add_submenu_page($this->settings_base, $this->plugin_data['Name'], $this->plugin_data['Name'], $this->settings_permission, $this->slug, array(&$this, 'show_admin'));
		add_action('admin_print_scripts-' . $page, array(&$this, 'load_admin_js'));
		add_action('admin_print_styles-' . $page, array(&$this, 'load_admin_css'));
	}

	// Load admin JS
	function load_admin_js() {
        wp_enqueue_script('jquery');
		wp_enqueue_script($this->slug . '-admin-js');
		wp_localize_script($this->slug . '-admin-js', 'tumadminJSlocal', array('approve' => __('Approve', 'th23-user-management')));
	}

	// Load admin CSS
	function load_admin_css() {
		wp_enqueue_style($this->slug . '-admin-css');
	}

	// Show admin page
	function show_admin() {

		global $wpdb;

		// Open wrapper and show plugin header
		echo '<div class="wrap">';
		echo '<img class="' . $this->slug . '-admin-icon" src="' . plugins_url('/img/admin-icon-48x41.png', $this->file) . '" /><h2>' . $this->plugin_data['Name'] . '</h2>';

		// Requirement details - warn user if requirements are not met
		if(is_multisite()) {
			echo '<div class="notice notice-warning"><p><strong>' . __('Warning', 'th23-user-management') . '</strong>: ' . __('This plugin is not designed to work on a multisite setup - it is recommended not to use this plugin in such an environment', 'th23-user-management') . '</p></div>';
		}

		// Display PRO information - if all requirements are met
		if(!$this->pro && $this->requirements) {
			echo '<div class="notice notice-info ' . $this->slug . '-admin-notice-upgrade" style="background-image: url(\'' . plugins_url('/img/admin-notice-upgrade-450x150.png', $this->file) . '\')">';
			/* translators: parses in "Professional" as name of the version */
			echo '<p>' . sprintf(__('You are currently using the free version of this plugin, in which we support a lot of very useful features to give your users a better feel browsing your site. To even improve that experience further, there is a %s version available adding some equally useful features:', 'th23-user-management'), '<strong><i>Professional</i></strong>') . '<ul>';
			echo '<li>' . __('<strong>All user management actions available on frontend</strong> styled according to theme - including profile changes, lost password, reset password', 'th23-user-management') . '</li>';
			/* translators: parses in file name "wp-login.php" */
			echo '<li>' . sprintf(__('<strong>Access to the unstyled admin area can be restricted</strong> based on user groups - %s can be disabled completely', 'th23-user-management'), '<code>wp-login.php</code>') . '</li>';
			echo '<li>' . __('<strong>User chosen password upon registration</strong> option available - including initial e-mail validation', 'th23-user-management') . '</li>';
			echo '<li>' . __('<strong>Admin approval for new users</strong> option available - before user can login', 'th23-user-management') . '</li>';
			echo '<li>' . __('<strong>Use reCaptcha against spam and bots</strong> upon registration, lost password and login', 'th23-user-management') . '</li>';
			echo '<li>' . __('Introduction of e-mail re-validation upon changes of address', 'th23-user-management') . '</li>';
			/* translators: 1: upgrade link to th23.net, 2: "Professional" as name of the version */
			echo '</ul>' . sprintf(__('Get all these additional features - <a href="%1$s">upgrade to the %2$s version today</a>', 'th23-user-management'), esc_url($this->plugin_data['PluginURI']), '<i>Professional</i>') . '</p>';
			echo '</div>';
		}

		// === UPDATE ===

		// Do update of plugin options if required
		if(isset($_POST[$this->slug . '-options-do'])) {
			check_admin_referer($this->plugin . '_settings', $this->slug . '-settings-nonce');
			$new_options = $this->get_options(array(), true);
			// IMPORTANT: Keep page_id static, as instanciated upon activation!
			$new_options['page_id'] = $this->options['page_id'];
			$update_done = false;
			if($new_options != $this->options) {
				update_option($this->plugin . '_options', $new_options);
				$update_done = true;
				$this->options = $new_options;
			}
			// Note: Following some options regarding user registration are handled specially, as they usually are set via Options - General
			$users_can_register = ((isset($_POST['users_can_register'])) ? 1 : 0);
			if(get_option('users_can_register') != $users_can_register) {
				update_option('users_can_register', $users_can_register);
				$update_done = true;
			}
			if(get_option('default_role') != $_POST['default_role'] && get_role($_POST['default_role'])) {
				update_option('default_role', $_POST['default_role']);
				$update_done = true;
			}
			// Show update message
			if($update_done) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Done', 'th23-user-management') . '</strong>: ' . __('Settings saved', 'th23-user-management') . '</p><button class="notice-dismiss" type="button"></button></div>';
			}
		}

		// Warn the user if user management "dummy" page is not found
		$user_management_page = get_page($this->options['page_id']);
		if(!isset($user_management_page) || !isset($user_management_page->post_status) || $user_management_page->post_status != 'publish') {
			// Note: Call both to be sure, we remove any unpublished / trashed older version AND create a properly registered new one!
			wp_delete_post($this->options['page_id'], true);
			$this->install();
			echo '<div class="notice notice-error"><p><strong>' . __('Error', 'th23-user-management') . '</strong>: ' . __('No valid/ published user management page has been found - it was automatically attempted to create a new one, please refresh this page and see if the error persists', 'th23-user-management') . '</p></div>';
		}

		// Warn the user if reCAPTCHA is activated, but no keys are defined
		if($this->options['captcha'] && (empty($this->options['captcha_public']) || empty($this->options['captcha_private']))) {
			echo '<div class="notice notice-error"><p><strong>' . __('Error', 'th23-user-management') . '</strong>: ' . __('reCAPTCHA requires a public and a private key to work - despite your settings it will be disabled until you define them, see settings below for information on how obtain these keys', 'th23-user-management') . '</p></div>';
		}

		// === SETTINGS ===

		$disabled_prop = ($this->pro) ? '' : ' disabled="disabled"';
		$disabled_class = ($this->pro) ? '' : ' disabled';

		echo '<form method="post" id="' . $this->slug . '-options" action="' . esc_url($this->settings_base_url) . '">';
		echo '<table class="form-table"><tbody>';

		echo '<tr valign="top"><th class="' . $this->slug . '-admin-section" colspan="2">' . __('General', 'th23-user-management') . '</th></tr>';

		// overlay_time
		echo '<tr valign="top">';
		echo ' <th scope="row"><label for="overlay_time">' . __('Overlay message time', 'th23-user-management') . '</label></th>';
		echo ' <td><input type="text" class="small-text" value="' . esc_attr($this->options['overlay_time']) . '" id="overlay_time" name="overlay_time" /><br /><span class="description">' . __('Duration in seconds until overlay messages shown to the user upon login/ logout disappears automatically - set to "0" for no automatic disappearance - Note: Error messages will never disappear automatically', 'th23-user-management') . '</span></td>';
		echo '</tr>';

		echo '<tr valign="top"><th class="' . $this->slug . '-admin-section" colspan="2">' . __('Access', 'th23-user-management') . '</th></tr>';

		// admin_access
		echo '<tr valign="top">';
		echo ' <th scope="row"><label for="admin_access">' . __('Access to admin area for', 'th23-user-management') . '</label></th>';
		echo ' <td><select class="postform' . $disabled_class . '" id="admin_access" name="admin_access"' . $disabled_prop . '>';
		$options = array(
			/* translators: following 5 strings name user groups as named in WP admin area */
			'install_themes' => __('Admins only', 'th23-user-management'),
			'edit_others_posts' => __('Admins and Editors only', 'th23-user-management'),
			'publish_posts' => __('Admins, Editors and Authors only', 'th23-user-management'),
			'edit_posts' => __('Admins, Editors, Authors and Contributors only [recommended]', 'th23-user-management'),
			'default' => __('All registered users (Admins, Editors, Authors, Contributors and Subscribers) [WordPress default]', 'th23-user-management')
		);
		foreach($options as $value => $title) {
			echo '<option value="' . $value . '"' . (($this->options['admin_access'] == $value) ? ' selected="selected"' : '') . '>' . $title . '</option>';
		}
		echo ' </select><br /><span class="description">' . __('Users not allowed to access the admin area will get an error message - forcing them to return to the home page of your site', 'th23-user-management') . '</span></td>';
		echo '</tr>';

		// admin_bar
		echo '<tr valign="top">';
		echo ' <th scope="row"><label for="admin_bar">' . __('Show admin bar for', 'th23-user-management') . '</label></th>';
		echo ' <td><select class="postform' . $disabled_class . '" id="admin_bar" name="admin_bar"' . $disabled_prop . '>';
		$options = array(
			/* translators: following 7 strings name user groups as named in WP admin area */
			'admin_access' => __('Groups having access to the admin area only [recommended]', 'th23-user-management'),
			'install_themes' => __('Admins only', 'th23-user-management'),
			'edit_others_posts' => __('Admins and Editors only', 'th23-user-management'),
			'publish_posts' => __('Admins, Editors and Authors only', 'th23-user-management'),
			'edit_posts' => __('Admins, Editors, Authors and Contributors only', 'th23-user-management'),
			'default' => __('All registered users (Admins, Editors, Authors, Contributors and Subscribers) [WordPress default]', 'th23-user-management'),
			'disable' => __('No one (disable admin bar)', 'th23-user-management')
		);
		foreach($options as $value => $title) {
			echo '<option value="' . $value . '"' . (($this->options['admin_bar'] == $value) ? ' selected="selected"' : '') . '>' . $title . '</option>';
		}
		echo ' </select><br /><span class="description">' . __('Defines which users will see the admin bar on the frontend of your site', 'th23-user-management') . '</span></td>';
		echo '</tr>';

		// allow_wplogin
		echo '<tr valign="top">';
		/* translators: parses in file name "wp-login.php" */
		echo ' <th scope="row">' . sprintf(__('Allow %s', 'th23-user-management'), '<code>wp-login.php</code>') . '</th>';
		echo ' <td><fieldset>';
		/* translators: parses in file name "wp-login.php" */
		echo '  <label for="allow_wplogin"><input type="checkbox" class="' . $disabled_class . '" value="1" id="allow_wplogin" name="allow_wplogin"' . (($this->options['allow_wplogin']) ? ' checked="checked"' : '') . $disabled_prop . '/> ' . sprintf(__('Allow access to %s', 'th23-user-management'), '<code>wp-login.php</code>') . '</label><br /><span class="description"><strong>' . __('Warning', 'th23-user-management') . '</strong>: ' . __('Enabling this setting users may circumvent some settings below, e.g. mail validation, admin approval, captcha - it is <strong>strongly recomended to leave this unchecked</strong>!', 'th23-user-management') . '</span></fieldset></td>';
		echo '</tr>';

		echo '<tr valign="top"><th class="' . $this->slug . '-admin-section" colspan="2">' . __('Registration', 'th23-user-management') . '</th></tr>';

		// users_can_register
		echo '<tr valign="top">';
		echo ' <th scope="row">' . __('Membership', 'th23-user-management') . '</th>';
		echo ' <td><fieldset><label for="users_can_register"><input name="users_can_register" type="checkbox" id="users_can_register" value="1"' . ((get_option('users_can_register')) ? ' checked="checked"' : '') . '/> ' . __('New users can register', 'th23-user-management') . '</label><br /><span class="description">' . __('This setting changes the standard defined under Settings - General', 'th23-user-management') . '</span></fieldset></td>';
		echo '</tr>';

		$sub_users_can_register_class = ($this->pro) ? ' class="user-registration-settings"' : '';
		$sub_users_can_register_pro_only = ($this->pro && !get_option('users_can_register')) ? ' style="display: none;"' : '';
		$sub_users_can_register = (!get_option('users_can_register')) ? ' style="display: none;"' : '';

		// password_user
		echo '<tr valign="top"' . $sub_users_can_register_class . $sub_users_can_register_pro_only . '>';
		echo ' <th scope="row"><span style="padding-left: 20px;">' . __('Password selection', 'th23-user-management') . '</span></th>';
		echo ' <td><fieldset><label for="password_user"><input type="checkbox" class="' . $disabled_class . '" value="1" id="password_user" name="password_user"' . (($this->options['password_user']) ? ' checked="checked"' : '') . $disabled_prop . '/> ' . __('Allow user to choose password upon registration - will require user to validate his mail address', 'th23-user-management') . '</label></fieldset></td>';
		echo '</tr>';

		// user_approval
		echo '<tr valign="top"' . $sub_users_can_register_class . $sub_users_can_register_pro_only . '>';
		echo ' <th scope="row"><span style="padding-left: 20px;">' . __('User approval', 'th23-user-management') . '</span></th>';
		echo ' <td><fieldset><label for="user_approval"><input type="checkbox" class="' . $disabled_class . '" value="1" id="user_approval" name="user_approval"' . (($this->options['user_approval']) ? ' checked="checked"' : '') . $disabled_prop . '/> ' . __('Newly registered users require approval by an admin before being allowed to login', 'th23-user-management') . '</label></fieldset></td>';
		echo '</tr>';

		$sub_user_approval_class = ($this->pro) ? ' class="user-approval-settings"' : '';
		$sub_user_approval = ($this->pro && (!get_option('users_can_register') || !$this->options['user_approval'])) ? ' style="display: none;"' : '';

		// registration_question
		echo '<tr valign="top"' . $sub_user_approval_class . $sub_user_approval . '>';
		echo ' <th scope="row"><label for="registration_question" style="padding-left: 40px;">' . __('Registration question', 'th23-user-management') . '</label></th>';
		echo ' <td><input type="text" class="regular-text' . $disabled_class . '" value="' . esc_attr($this->options['registration_question']) . '" id="registration_question" name="registration_question"' . $disabled_prop . '/><br /><span class="description">' . __('Question to request additional information from users upon registration, e.g. "How did you find out about this page?" to determine if the request for approving a newly registered user is valid or should be denied - leave empty to not show any question', 'th23-user-management') . '</span></td>';
		echo '</tr>';

		// default_role
		echo '<tr valign="top"' . $sub_user_approval_class . $sub_user_approval . '>';
		echo ' <th scope="row"><label for="default_role" style="padding-left: 40px;">' . __('New User Default Role', 'th23-user-management') . '</label></th>';
		echo ' <td><select name="default_role" id="default_role">';
		wp_dropdown_roles(get_option('default_role'));
		echo ' </select><br /><span class="description">' . __('In case admin approval is required for new users, they will be assigned to this selection after the approval has been granted by an admin!<br/>This setting changes the standard defined under Settings - General', 'th23-user-management') . '</span></td>';
		echo '</tr>';

		// approver_mail
		echo '<tr valign="top"' . $sub_user_approval_class . $sub_user_approval . '>';
		echo ' <th scope="row"><label for="approver_mail" style="padding-left: 40px;">' . __('Approver e-mail', 'th23-user-management') . '</label></th>';
		echo ' <td><input type="text" class="regular-text" value="' . esc_attr($this->options['approver_mail']) . '" id="approver_mail" name="approver_mail"' . $disabled_prop . '/><br /><span class="description">' . __('Provide mail address of the approver, to receive notification mails on each new user registration pending approval - leave empty to receive no notifications', 'th23-user-management') . '</span></td>';
		echo '</tr>';

		// terms
		echo '<tr valign="top" class="user-registration-settings"' . $sub_users_can_register . '>';
		echo ' <th scope="row"><span style="padding-left: 20px;">' . __('Terms and conditions', 'th23-user-management') . '</span></th>';
		echo ' <td><fieldset><label for="terms"><input type="checkbox" class="' . $disabled_class . '" value="1" id="terms" name="terms"' . (($this->options['terms']) ? ' checked="checked"' : '') . '/> ' . __('New users are required to accept terms of usage before being able to complete the registration', 'th23-user-management') . '</label></fieldset>';
		echo ' <span class="description">' . __('Example:', 'th23-user-management');
		$terms = (empty($title = get_option('th23_terms_title'))) ? __('Terms of Usage', 'th23-user-management') : $title;
		$terms = ($url = get_option('th23_terms_url')) ? '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($terms) . '</a>' : esc_html($terms);
		/* translators: %s: link with/or title to sites terms & conditions, as defined by admin */
		echo ' <input type=checkbox />' . sprintf(__('I accept the %s, agree with processing my data and the usage of cookies', 'th23-user-management'), $terms);
		/* translators: %s: link to general options page in admin */
		echo ' <br />' . sprintf(__('Note: For changing title and link shown see %s', 'th23-user-management'), '<a href="options-general.php#th23_terms">' . __('General Settings') . '</a>') . '</span></td>';
		echo '</tr>';

		echo '<tr valign="top"><th class="' . $this->slug . '-admin-section" colspan="2">' . __('Security', 'th23-user-management') . '</th></tr>';

		// captcha
		echo '<tr valign="top">';
		/* translators: parses in "reCaptcha" as name of the service */
		echo ' <th scope="row">' . sprintf(__('Enable %s', 'th23-user-management'), '<i>reCaptcha</i>') . '</th>';
		/* translators: 1: "reCaptcha" as name of the service, 2: "Google" as company name, 3: link to Google reCaptcha website */
		echo ' <td><fieldset><label for="captcha"><input type="checkbox" class="' . $disabled_class . '" value="1" id="captcha" name="captcha"' . (($this->options['captcha']) ? ' checked="checked"' : '') . $disabled_prop . '/> ' . sprintf(__('Enable usage of %1$s for better protection against spam and bot registrations - Note: This is a service provided by %2$s and requires <a href="%3$s">signing up for a free key</a>', 'th23-user-management'), '<i>reCaptcha</i>', '<i>Google</i>', 'https://www.google.com/recaptcha/intro/index.html') . '</label></fieldset></td>';
		echo '</tr>';

		$sub_captcha_class = ($this->pro) ? ' class="captcha-settings"' : '';
		$sub_captcha = ($this->pro && (!$this->options['captcha'])) ? ' style="display: none;"' : '';

		// captcha_public
		echo '<tr valign="top"' . $sub_captcha_class . $sub_captcha . '>';
		echo ' <th scope="row"><label for="captcha_public" style="padding-left: 20px;">' . __('Public key', 'th23-user-management') . '</label></th>';
		/* translators: parses in "reCaptcha" as name of the service */
		echo ' <td><input type="text" class="regular-text' . $disabled_class . '" value="' . esc_attr($this->options['captcha_public']) . '" id="captcha_public" name="captcha_public"' . $disabled_prop . '/><br /><span class="description">' . sprintf(__('Required, public %s key - see above link to obtain a key', 'th23-user-management'), '<i>reCaptcha</i>') . '</span></td>';
		echo '</tr>';

		// captcha_private
		echo '<tr valign="top"' . $sub_captcha_class . $sub_captcha . '>';
		echo ' <th scope="row"><label for="captcha_private" style="padding-left: 20px;">' . __('Private key', 'th23-user-management') . '</label></th>';
		/* translators: parses in "reCaptcha" as name of the service */
		echo ' <td><input type="text" class="regular-text' . $disabled_class . '" value="' . esc_attr($this->options['captcha_private']) . '" id="captcha_private" name="captcha_private"' . $disabled_prop . '/><br /><span class="description">' . sprintf(__('Required, private %s key - see above link to obtain a key', 'th23-user-management'), '<i>reCaptcha</i>') . '</span></td>';
		echo '</tr>';

		// captcha_register
		echo '<tr valign="top"' . $sub_captcha_class . $sub_captcha . '>';
		echo ' <th scope="row"><span style="padding-left: 20px;">' . __('Registration captcha', 'th23-user-management') . '</span></th>';
		echo ' <td><fieldset><label for="captcha_register"><input type="checkbox" class="' . $disabled_class . '" value="1" id="captcha_register" name="captcha_register"' . (($this->options['captcha_register']) ? ' checked="checked"' : '') . $disabled_prop . '/> ' . __('Users need to solve a captcha upon registering for a new account', 'th23-user-management') . '</label></fieldset></td>';
		echo '</tr>';

		// captcha_lostpassword
		echo '<tr valign="top"' . $sub_captcha_class . $sub_captcha . '>';
		echo ' <th scope="row"><span style="padding-left: 20px;">' . __('Lost password captcha', 'th23-user-management') . '</span></th>';
		echo ' <td><fieldset><label for="captcha_lostpassword"><input type="checkbox" class="' . $disabled_class . '" value="1" id="captcha_lostpassword" name="captcha_lostpassword"' . (($this->options['captcha_lostpassword']) ? ' checked="checked"' : '') . $disabled_prop . '/> ' . __('Users need to solve a captcha upon requesting a password reset', 'th23-user-management') . '</label></fieldset></td>';
		echo '</tr>';

		// captcha_login
		echo '<tr valign="top"' . $sub_captcha_class . $sub_captcha . '>';
		echo ' <th scope="row"><label for="captcha_login" style="padding-left: 20px;">' . __('Login captcha', 'th23-user-management') . '</label></th>';
		echo ' <td><input type="text" class="small-text' . $disabled_class . '" value="' . esc_attr($this->options['captcha_login']) . '" id="captcha_login" name="captcha_login"' . $disabled_prop . '/><br /><span class="description">' . __('Specify at which attempt (unsuccessful, in a row) users need to solve a captcha upon login - set to "0" to disable, set to e.g. "4" for allowing three attempts without captcha', 'th23-user-management') . '</span></td>';
		echo '</tr>';

		echo '</tbody></table>';
		echo '<br/>';

		// submit
		echo '<input type="hidden" name="' . $this->slug . '-options-do" value=""/>';
		echo '<input type="button" id="' . $this->slug . '-options-submit" class="button-primary" value="' . esc_attr(__('Save Changes', 'th23-user-management')) . '"/>';
		wp_nonce_field($this->plugin . '_settings', $this->slug . '-settings-nonce');

		echo '</form>';
		echo '<br/>';

		// Plugin information
		if($this->pro) {
			$version_details = ' <span class="' . $this->slug . '-admin-professional">Professional</span>';
			$about_link = '<div class="' . $this->slug . '-admin-about-feedback"><a href="' . esc_url($this->support_url) . '"><img src="' . plugins_url('/img/admin-about-feedback-309x100.png', $this->file) . '" alt="" /></a></div>';
		}
		else {
			/* translators: parses in "Professional" as name of the version */
			$version_details = ' <span class="' . $this->slug . '-admin-basic">Basic</span> - <a href="' . esc_url($this->plugin_data['PluginURI']) . '" class="' . $this->slug . '-admin-about-upgrade">' . sprintf(__('Upgrade to %s version', 'th23-user-management'), '<i>Professional</i>') . '</a>';
			$about_link = '<div class="' . $this->slug . '-admin-about-upgrade"><a href="' . esc_url($this->plugin_data['PluginURI']) . '"><img src="' . plugins_url('/img/admin-about-upgrade-275x70.png', $this->file) . '" /></a></div>';
		}
		echo '<div class="' . $this->slug . '-admin-about"><p><strong>' . $this->plugin_data['Name'] . '</strong>';
		/* translators: parses in plugin version number */
		echo ' | ' . sprintf(__('Version %s', 'th23-user-management'), $this->plugin_data['Version']) . $version_details;
		/* translators: parses in plugin author name */
		echo ' | ' . sprintf(__('By %s', 'th23-user-management'), $this->plugin_data['Author']);
		if(!empty($this->support_url)) {
			echo ' | <a href="' . esc_url($this->support_url) . '">' . __('Support', 'th23-user-management') . '</a>';
		}
		elseif(!empty($this->plugin_data['PluginURI'])) {
			echo ' | <a href="' . $this->plugin_data['PluginURI'] . '">' . __('Visit plugin site', 'th23-user-management') . '</a>';
		}
		echo $about_link;
		echo '</p></div>';

		// Close wrapper
		echo '</div>';

	}

	// Enhance user page by adding colums showing dates "Registered", "Last Login", "Last Visit"
	function users_columns($columns) {
		$columns['registered'] = __('Registered', 'th23-user-management');
		$columns['last_login'] = __('Last Login', 'th23-user-management');
		$columns['last_visit'] = __('Last Visit', 'th23-user-management');
		return $columns;
	}
	function users_custom_columns($value, $column_name, $user_id) {
		if($column_name == 'registered') {
			$user = get_userdata($user_id);
			$datetime = strtotime(get_date_from_gmt($user->user_registered));
		}
		elseif($column_name == 'last_login') {
			$datetime = get_user_meta($user_id, 'th23-user-management-last-login', true);
		}
		elseif($column_name == 'last_visit') {
			$datetime = get_user_meta($user_id, 'th23-user-management-last-visit', true);
		}
		else {
			return;
		}
		return ($datetime) ? date_i18n(get_option('date_format'), $datetime) . '<br />' . date_i18n(get_option('time_format'), $datetime) : __('Never', 'th23-user-management');
	}
	function users_sortable_columns($columns) {
		$columns['registered'] = 'registered';
		$columns['last_login'] = 'last_login';
		$columns['last_visit'] = 'last_visit';
		return $columns;
	}
	function users_orderby_column($vars) {
		// Note: "registered" works without addition, as its part of the standard user data set
		if(isset($vars['orderby']) && $vars['orderby'] == 'last_login') {
			$vars['meta_key'] = 'th23-user-management-last-login';
			$vars['orderby'] = 'meta_value';
		}
		elseif(isset($vars['orderby']) && $vars['orderby'] == 'last_visit') {
			$vars['meta_key'] = 'th23-user-management-last-visit';
			$vars['orderby'] = 'meta_value';
		}
		return $vars;
	}

}

?>
