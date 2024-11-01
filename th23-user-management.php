<?php
/*
Plugin Name: th23 User Management
Plugin URI: http://th23.net/th23-user-management
Description: User management activities (login, profile changes, register, lost password) can be done via the themed frontend of your website. Access for user groups to unstyled admin area can be restricted and "wp-login.php" can be disabled. Users will only see the nicely styled side of your page :-) Add options for user chosen password upon registration including initial e-mail validation and approval for new registrations by administrator. Option to use reCaptcha to prevent spam and bots upon registration, lost password and login. Introduce e-mail re-validation upon changes. Note: Some options are limited in the Basic version!
Version: 2.4.0
Author: Thorsten Hartmann (th23)
Author URI: http://th23.net
Text Domain: th23-user-management
Domain Path: /lang
License: GPLv2 only
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright 2010-2019, Thorsten Hartmann (th23)
http://th23.net

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License version 2, as published by the Free Software Foundation. You may NOT assume that you can use any other version of the GPL.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
This license and terms apply for the Basic part of this program as distributed, but NOT for the separately distributed Professional add-on!
*/

class th23_user_management {

	// === SETUP ===

	var $plugin, $slug, $file, $version, $base_dir, $base_name, $pro, $pro_version, $options, $requirements, $settings_base, $settings_base_url, $settings_permission, $plugin_data, $data; // $data for exchange of data between plugin functions

	function __construct() {

		// Setup basic variables
		$this->plugin = 'th23_user_management';
		$this->slug = 'th23-user-management';
		$this->file = __FILE__;
		$this->version = '2.4.0'; // for dev: $this->version = time();
		$this->base_dir = TH23_USER_MANAGEMENT_BASEDIR;
		$this->base_name = TH23_USER_MANAGEMENT_BASENAME;
		$this->pro = false;

		// Load plugin options
		$this->options = (array) get_option($this->plugin . '_options');

		// Localization
		load_plugin_textdomain('th23-user-management', false, dirname($this->base_name) . '/lang');

		// Initialize basics
		$this->data['msg'] = array();
		$this->login_init();
		$this->reauth_init();
		$this->logout_init();
		$this->trouble_init();
		$this->register_init();
		$this->edit_profile_init();

		// Prevent user management page from showing up in search results
		add_filter('pre_get_posts', array(&$this, 'hide_from_search'));

		// Early execution point, eg for cookie related actions / before sending any other output
		add_action('send_headers', array(&$this, 'do_early'));

		// Hanlde JS and CSS
		add_action('init', array(&$this, 'register_js_css'));
		add_action('template_redirect', array(&$this, 'load_js_css'));

		// Normal execution point for any plugin actions
		add_action('get_header', array(&$this, 'do_normal'));

		// Prepare user management page, including title - execute after all "do" actions above
		add_action('get_header', array(&$this, 'prepare_output'), 11);

		// Show user management page - title and content
		add_filter('wp_title_parts', array(&$this, 'head_title'));
		add_filter('the_title', array(&$this, 'page_title'), 10, 2);
		add_filter('the_content', array(&$this, 'page_content'));

		// Add link for edit profile page to standard "Meta" widget
		add_action('wp_meta', array(&$this, 'add_edit_profile_link'));

		// Add overlay message HTML if required
		add_action('wp_footer', array(&$this, 'add_overlay'));

		// Initiate and update last login / last visit information for user
		add_action('user_register', array(&$this, 'initiate_last_login_visit'));
		add_action('wp_login', array(&$this, 'update_last_login'));
		add_action('wp_footer', array(&$this, 'update_last_visit'));

	}

	// Ensure PHP <5 compatibility
	function th23_user_management() {
		self::__construct();
	}

	// Prevent user management page from showing up in search results
	function hide_from_search($query) {
		if($query->is_search) {
			$query->set('post__not_in', array($this->options['page_id']));
		}
		return $query;
	}

	// Execute early actions and independent from user management page - eg for cookie related actions / before sending any other output
	function do_early() {
		// Note: Avoid double execution of actions or execution of actions for multiple different screens at once!
		// Therefore all functions hooking into this hook should include the following in the VERY beginning:
		// Priority between actions is specified via the priority of hooks, e.g. 1 for re-authentication vs. 5 for troubleshooting vs. 10 for register vs. 15 for login
		/*
		*	if(!empty($this->data['action_done'])) {
		*		return;
		*	}
		*	// Decide, if we are asked to do actions...
		*	$this->data['action_done'] = true;
		*	// Do actions...
		*/
		do_action($this->plugin . '_do_early');
	}

	// Register JS and CSS
	function register_js_css() {

		// Register standard JS and CSS
		wp_register_script($this->slug . '-js', plugins_url('/' . $this->slug . '.js', $this->file), array('jquery'), $this->version, true);
		wp_register_style($this->slug . '-css', plugins_url('/' . $this->slug . '.css', $this->file), array(), $this->version);

		do_action($this->plugin . '_register_js_css');

	}

	// Load JS and CSS
	function load_js_css() {

		// Load and customize standard JS and CSS
		wp_enqueue_script('jquery'); // should be already loaded, but let's make sure jQuery is there
		wp_enqueue_script($this->slug . '-js');
		wp_localize_script($this->slug . '-js', 'tumJSlocal', array('profile_url' => admin_url() . 'profile.php', 'user_management_url' => $this->user_management_url(), 'omsg_timeout' => $this->options['overlay_time'] * 1000));
		wp_enqueue_style($this->slug . '-css');

		do_action($this->plugin . '_load_js_css');

	}

	// Execute normal actions / on user management page
	function do_normal() {
		// Return, if we do not show the user management page
		if(!is_page($this->options['page_id'])) {
			return;
		}
		// Note: Avoid double execution!
		// ==> see remarks to "do_early" above for doing it correct!!!
		do_action($this->plugin . '_do_normal');
	}

	// Prepare user management page, including title - after all "do" actions have been done
	function prepare_output() {
		// Return, if we do not show the user management page
		if(!is_page($this->options['page_id'])) {
			return;
		}
		// Note: We can only show one page!
		// Therefore all functions hooking into this hook should include the following in the VERY beginning:
		// Where needed priority between pages is specified via the priority of hooks, e.g. 10 for register vs 15 for login (as fallback)
		/*
		*	if(!empty($this->data['page_content'])) {
		*		return;
		*	}
		*	// Decide, if we are asked to show HTML...
		*	$this->data['page_title'] = 'Title';
		*	$this->data['page_content'] = '<div>HTML</div>';
		*/
		do_action($this->plugin . '_prepare_output');
	}

	// Show title of user management page - as page title
	function head_title($title_array) {
		if(empty($this->data['page_title'])) {
			return $title_array;
		}
		return array($this->data['page_title']);
	}

	// Show title of user management page - as post title
	function page_title($title, $id) {
		if(empty($this->data['page_title']) || $id != $this->options['page_id']) {
			return $title;
		}
		return '<span class="th23-user-management-title">' . $this->data['page_title'] . '</span>';
	}

	// Show user management page
	function page_content($content) {
		if(empty($this->data['page_content'])) {
			return $content;
		}

		// Show messages/ errors generated - eg upon execution of actions earlier
		$message_html = '';
		$error = false;
		foreach($this->data['msg'] as $message) {
			if($message['type'] == 'success') {
				$message_head = __('Done', 'th23-user-management');
			}
			elseif($message['type'] == 'error') {
				$error = true;
				$message_head = __('Error', 'th23-user-management');
			}
			else {
				$message_head = __('Info', 'th23-user-management');
			}
			$message_html .= '<div class="th23-message th23-user-management-message ' . $message['type'] . '"><strong>' . $message_head . '</strong>: ' . $message['text'] . '</div>';
		}

		return '<div class="th23-user-management-form">' . $message_html . $this->data['page_content'] . '</div>';

	}

	// Add link for edit profile page to standard "Meta" widget
	function add_edit_profile_link() {
		if(is_user_logged_in()) {
			echo '<li><a href="' . esc_url($this->user_management_url()) . '">' . __('Edit profile', 'th23-user-management') . '</a></li>';
		}
	}

	// Add overlay message HTML if required
	function add_overlay() {
		if(isset($this->data['omsg'])) {
			$omsg = apply_filters($this->plugin . '_overlay_message', $this->data['omsg']);
			if(is_array($omsg)) {
				echo '<div class="th23-omsg th23-user-management-omsg ' . $omsg['type'] . '">';
				echo '<div class="headline"><div class="title">' . $omsg['title'] . '</div><div class="close"></div></div>';
				echo '<div class="message">' . $omsg['text'] . '</div>';
				echo '</div>';
			}
		}
	}

	// Initiate last login / last visit information for new users
	function initiate_last_login_visit($user_id) {
		if(empty(get_user_meta($user_id, 'th23-user-management-last-login', true))) {
			update_user_meta($user_id, 'th23-user-management-last-login', 0);
		}
		if(empty(get_user_meta($user_id, 'th23-user-management-last-visit', true))) {
			update_user_meta($user_id, 'th23-user-management-last-visit', 0);
		}
	}

	// Update last login information for user
	function update_last_login($login) {
		$user = get_user_by('login', $login);
		update_user_meta($user->ID, 'th23-user-management-last-login', current_time('timestamp'));
	}

	// Update last visit information for user
	function update_last_visit() {
		if(is_user_logged_in()) {
			$user = wp_get_current_user();
			update_user_meta($user->ID, 'th23-user-management-last-visit', current_time('timestamp'));
		}
	}

	// === COMMON ===

	// Get checked plugin options
	function get_options($new_options = array(), $require_post = false) {

		// Plugin options
		$checked_options = array(
			'page_id' => array(0), // ID of the dummy page to be used the show the user management
			'overlay_time' => array(5), // duration the overlay messages upon login/ logout are shown to the user in seconds, before they disappears automatically - set to "0" for no automatic disappearance
			'admin_access' => array('edit_posts', 'install_themes', 'edit_others_posts', 'publish_posts', 'default'), // restrict admin access to "install_themes" (Admins), "edit_others_posts" (Admins, Editors), "publish_posts" (Admins, Editors, Authors), "edit_posts" (Admins, Editors, Authors, Contributors [recommended]), "default" (All registered: Admins, Editors, Authors, Contributors, Subscribers [WordPress default])
			'admin_bar' => array('admin_access', 'install_themes', 'edit_others_posts', 'publish_posts', 'edit_posts', 'default', 'disable'), // show admin bar on frontend to "admin_access" (uses setting as defined above for having access to the admin area [recommended]), "install_themes" (Admins), "edit_others_posts" (Admins, Editors), "publish_posts" (Admins, Editors, Authors), "edit_posts" (Admins, Editors, Authors, Contributors), "default" (All registered: Admins, Editors, Authors, Contributors, Subscribers [WordPress default]), "disable" (deactivate admin bar completely)
			'allow_wplogin' => array(false, true), // allow access to wp-login.php - note: risk of circumventing some of settings below, e.g. for captcha, user approval, etc
			// 'users_can_register' - general setting can be influenced via this plugin, but is stored only in overall WordPress settings
			'password_user' => array(0), // allow user to choose password upon regsitration - will require user to validate his mail address (0 = no, 1 = yes)
			'user_approval' => array(0), // new user registrations require admin approval (0 = no, 1 = yes)
			'registration_question' => array(''), // question to request additional information from users upon registration, e.g. how did you find out about this page? (disabled, if empty)
			// 'default_role' - general setting can be influenced via this plugin, but is stored only in overall WordPress settings
			'approver_mail' => array(''), // mail address of an approver, that will receive a notification mail on each new user registration pending approval
			'captcha' => array(0), // enable captcha (0 = no, 1 = yes)
			'captcha_public' => array(''), // public / site key for reCAPTCHA - sign up for a key under https://www.google.com/recaptcha/intro/index.html (it's for free)
			'captcha_private' => array(''), // private / secret key for reCAPTCHA - sign up for a key under https://www.google.com/recaptcha/intro/index.html (it's for free)
			'captcha_register' => array(0), // use captcha upon registration of new users (0 = no, 1 = yes)
			'captcha_lostpassword' => array(0), // use captcha upon request for password resets (0 = no, 1 = yes)
			'captcha_login' => array(0), //  use captcha upon login of users - specify on what login attempt the users have to solve a captcha, e.g. setting this to 4 will ask users only after three failed attempts in a row to solve the captcha (0 = no, 1 = each login, 2 = only after one unsuccessful attempt, ...)
			'terms' => array(0) // require new users upon registration to acknowledge terms of usage, especially agree to processing data and usage of cookies - new EU data privacy law, GDPR (0 = no, 1 = yes)
		);

		if($require_post) {
			foreach($checked_options as $option => $default) {
				if(isset($_POST[$option])) {
					$new_options[$option] = stripslashes($_POST[$option]);
				}
			}
		}

		foreach($checked_options as $option => $default) {
			if(isset($new_options[$option])) {
				if(gettype($default[0]) != gettype($new_options[$option])) {
					settype($new_options[$option], gettype($default[0]));
				}
				if(count($default) > 1 && !in_array($new_options[$option], $default)) {
					$checked_options[$option] = $default[0];
					continue;
				}
				$checked_options[$option] = $new_options[$option];
				continue;
			}
			$checked_options[$option] = $default[0];
		}

		return $checked_options;

	}

	// Get current URL - and required connector
	function get_current_url() {
		$current_url = (is_ssl() ? 'https' : 'http').'://';
		$current_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		if(strpos(home_url(), '://www.') === false) {
			$current_url = str_replace('://www.', '://', $current_url);
		}
		else {
			if(strpos($current_url, '://www.') === false) {
				$current_url = str_replace('://', '://www.', $current_url);
			}
		}
		return $current_url;
	}

	// Define "user_managment_url"
	function user_management_url($params = '') {
		$url = get_permalink($this->options['page_id']);
		if($params) {
			$url .= (strpos($url, '?') !== false) ? '&' . $params : '?' . $params;
		}
		return $url;
	}

	// Define "redirect_to_url" based on redirect_to - it should start with the home_url
	function redirect_to_url() {
		return (!empty($_REQUEST['redirect_to']) && strpos($_REQUEST['redirect_to'], home_url()) == 0) ? $_REQUEST['redirect_to'] : home_url();
	}

	// === LOGIN ===

	// Init login functionality
	function login_init() {

		add_filter('login_url', array(&$this, 'login_url'), 10, 2);
		add_action($this->plugin . '_do_early', array(&$this, 'login_do_early'));
		add_action($this->plugin . '_prepare_output', array(&$this, 'login_prepare_output'), 15); // show later than others (= 15), so it can be the fallback for not logged-in users
		add_filter($this->plugin . '_overlay_message', array(&$this, 'login_overlay_message'));

		// Get omsg (overlay message) parameter, store internally and remove them from request URI
		if(isset($_GET['omsg']) && $_GET['omsg'] == 'login_success') {
			$this->data['omsg'] = $_GET['omsg'];
			unset($_GET['omsg']);
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('omsg'));
		}

	}

	// Modify login_url used by WordPress
	function login_url($login_url, $redirect) {
		$login_url = (!empty($redirect)) ? 'login&redirect_to=' . urlencode($redirect) : 'login';
		return $this->user_management_url($login_url);
	}

	// Execute login attempt
	function login_do_early() {
		// Has anything else already been done?
		if(!empty($this->data['action_done'])) {
			return;
		}
		// Do we have something to do - and is it allowed to do this?
		if(!isset($_REQUEST['login'])) {
			return;
		}
		$this->data['action_done'] = true;

		// Prepare test for acceptance of cookies
		setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
		if(SITECOOKIEPATH != COOKIEPATH) {
			setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);
		}

		if(!isset($_REQUEST['submit'])) {
			return;
		}

		// Validate nonce - abort login attempt if failed
		if(!wp_verify_nonce($_POST[$this->slug . '-login-nonce'], $this->plugin . '_login')) {
			$this->data['msg'][] = array('type' => 'error', 'text' => __('Invalid request - please use the form below to login', 'th23-user-management'));
			unset($_POST['log']);
			unset($_POST['pwd']);
			return;
		}

		// Check if user was required to solve a captcha and did so successfully - see PRO class
		do_action($this->plugin . '_login_captcha_validate');
		if(!empty($this->data['msg'])) {
			unset($_POST['pwd']);
			return;
		}

		// Check for cookie acceptance
		if(isset($_POST['testcookie']) && empty($_COOKIE[TEST_COOKIE])) {
			$this->data['msg'][] = array('type' => 'error', 'text' => __('Cookies are blocked or not supported by your browser. You must <a href="https://en.wikipedia.org/wiki/HTTP_cookie">enable cookies</a> to login on this site', 'th23-user-management'));
			return;
		}

		// Let's do the magic!
		$login = wp_signon();
		if(!is_wp_error($login)) {
			do_action($this->plugin . '_login_success', $login->ID);
			wp_safe_redirect(esc_url_raw(add_query_arg('omsg', 'login_success', $this->redirect_to_url())));
			exit;
		}

		// Show message on what went wrong
		$error_code = $login->get_error_code();
		if($error_code == 'empty_username') {
			$error_message = __('Enter a username', 'th23-user-management');
		}
		elseif($error_code == 'invalid_username') {
			$error_message = __('The username you entered is invalid', 'th23-user-management');
		}
		elseif($error_code == 'empty_password') {
			$error_message = __('Enter your password', 'th23-user-management');
		}
		elseif($error_code == 'incorrect_password') {
			$error_message = __('The password you entered is incorrect', 'th23-user-management');
		}
		else {
			$error_message = $login->get_error_message();
		}
		if(!empty($error_message)) {
			$this->data['msg'][] = array('type' => 'error', 'text' => $error_message);
		}

	}

	// Prepare login page
	function login_prepare_output() {
		// Any output yet defined?
		if(!empty($this->data['page_content'])) {
			return;
		}
		// Are we asked to show something?
		// Do not show, if user is logged in and no re-authentication is triggered - otherwise no check, as we are the fallback
		if(is_user_logged_in() && empty($_REQUEST['reauth'])) {
			return;
		}

		$this->data['page_title'] = __('Login', 'th23-user-management');

		$html = '<p class="message">' . __('Please login, to have full access to this site.', 'th23-user-management') . '</p>';
		$html .= '<form name="loginform" id="loginform" action="' . esc_url($this->user_management_url('login')) . '" method="post">';
		$html .= '<p><span class="th23-user-management-input-wrap">';
		$html .= '<label for="log">' . __('Username', 'th23-user-management') . '</label>';
		$log = (isset($_REQUEST['log'])) ? esc_attr(wp_unslash($_REQUEST['log'])) : '';
		$html .= '<input type="text" name="log" id="log" class="input" value="' . $log . '" size="20" />';
		$html .= '</span></p>';
		$html .= '<p><span class="th23-user-management-input-wrap">';
		$html .= '<label for="pwd">' . __('Password', 'th23-user-management') . '</label>';
		$html .= '<input type="password" name="pwd" id="pwd" class="input" value="" size="20" />';
		$html .= '</span></p>';
		$html .= '<p>';
		$html .= '<input name="rememberme" type="checkbox" id="rememberme" value="forever"' . (!empty($_POST['rememberme']) ? ' checked="checked"' : '') . ' /> <label for="rememberme">' . __('Remember me', 'th23-user-management') . '</label>';
		$html .= '</p>';
		$html .= apply_filters($this->plugin . '_login_captcha_html', '');
		$html .= '<p class="submit">';
		$html .= '<input type="submit" name="submit" id="submit" class="button-primary" value="' . esc_attr(__('Log in', 'th23-user-management')) . '" />';
		$html .= '<input type="hidden" name="redirect_to" value="' . esc_attr((!empty($_REQUEST['redirect_to'])) ? $_REQUEST['redirect_to'] : home_url()) . '" />';
		$html .= wp_nonce_field($this->plugin . '_login', $this->slug . '-login-nonce', true, false);
		if(isset($_REQUEST['login'])) {
			$html .= '<input type="hidden" name="testcookie" value="1" />';
		}
		$html .= '</p>';
		$html .= '</form>';
		$html .= '<div class="th23-user-management-links"><ul>';
		$html .= '<li><a href="' . esc_url(wp_lostpassword_url()) . '" >' . __('Lost your password?', 'th23-user-management') . '</a></li>';
		if(get_option('users_can_register')) {
			$html .= '<li><a href="' . esc_url($this->user_management_url('register')) . '" >' . __('Register', 'th23-user-management') . '</a></li>';
		}
		$html .= '<li><a href="' . esc_url($this->user_management_url('trouble')) . '" >' . __('Trouble upon login?', 'th23-user-management') . '</a></li>';
		$html .= '</ul></div>';

		$this->data['page_content'] = $html;

	}

	// Translate "omsg" value into overlay message
	function login_overlay_message($omsg) {
		if($omsg != 'login_success') {
			return $omsg;
		}

		$current_user = wp_get_current_user();

		if(empty($_COOKIE[TEST_COOKIE])) {
			return array(
				'type' => 'error',
				'title' => __('Error', 'th23-user-management'),
				'text' => __('Cookies are blocked or not supported by your browser. You must <a href="https://en.wikipedia.org/wiki/HTTP_cookie">enable cookies</a> to login on this site', 'th23-user-management')
			);
		}
		elseif(get_user_option('default_password_nag', $current_user->ID)) {
			return array(
				'type' => 'info',
				'title' => sprintf(__('Welcome %s', 'th23-user-management'), esc_html($current_user->display_name)),
				'text' => sprintf(__('You have been logged in successfully, but you have not changed your initial password yet - please visit <a href="%s">your profile</a> to do so', 'th23-user-management'), esc_url(get_edit_profile_url()))
			);
		}
		else {
			return array(
				'type' => 'success',
				'title' => sprintf(__('Welcome %s', 'th23-user-management'), esc_html($current_user->display_name)),
				'text' => __('You have been logged in successfully', 'th23-user-management')
			);
		}

	}

	// === RE-AUTHENTICATION ===

	// Init re-authentication functionality
	function reauth_init() {
		// We do this before anything else - if reauth is triggered, then first of all destroy previous sessions!
		add_action($this->plugin . '_do_early', array(&$this, 'reauth_do_early'), 1);
	}

	// Handel special case of re-authentication requests
	function reauth_do_early() {
		// Has anything else already been done?
		if(!empty($this->data['action_done'])) {
			return;
		}
		// Do we have something to do - and is it allowed to do this?
		if(isset($_REQUEST['reauth'])) {
			wp_clear_auth_cookie();
			wp_destroy_all_sessions();
			wp_safe_redirect(esc_url_raw($this->user_management_url('reauthenticate&redirect_to=' . urlencode($this->redirect_to_url()))));
			exit;
		}
		elseif(isset($_REQUEST['reauthenticate'])) {
			$this->data['action_done'] = true; // not really necessary, as we got redirected here...but to avoid somebody messing around
			$this->data['msg'][] = array('type' => 'info', 'text' => __('You are required to re-authenticate, please login again', 'th23-user-management'));
			// handle as normal login attempt going forward - but not as submitted
			unset($_REQUEST['reauthenticate']);
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('reauthenticate'));
		}
		elseif(isset($_REQUEST['changedpassword'])) {
			$this->data['action_done'] = true; // not really necessary, as we got redirected here...but to avoid somebody messing around
			$this->data['msg'][] = array('type' => 'info', 'text' => __('Your password has been changed, please login again', 'th23-user-management'));
			// handle as normal login attempt going forward - but not as submitted
			unset($_REQUEST['changedpassword']);
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('changedpassword'));
		}
	}

	// === LOGOUT ===

	// Init login functionality
	function logout_init() {

		add_filter('logout_url', array(&$this, 'logout_url'));
		add_action($this->plugin . '_do_early', array(&$this, 'logout_do_early'));
		add_filter($this->plugin . '_overlay_message', array(&$this, 'logout_overlay_message'));

		// Get omsg (overlay message) parameter, store internally and remove them from request URI
		if(isset($_GET['omsg']) && $_GET['omsg'] == 'logout_success') {
			$this->data['omsg'] = $_GET['omsg'];
			unset($_GET['omsg']);
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('omsg'));
		}

	}

	// Modify logout_url used by WordPress
	function logout_url() {
		return wp_nonce_url($this->user_management_url('logout&redirect_to=' . urlencode(home_url())), 'logout', 'nonce');
	}

	// Execute logout
	function logout_do_early() {
		// Has anything else already been done?
		if(!empty($this->data['action_done'])) {
			return;
		}
		// Do we have something to do - and is it allowed to do this?
		if(!isset($_REQUEST['logout'])) {
			return;
		}
		$this->data['action_done'] = true;

		check_admin_referer('logout', 'nonce');
		wp_logout();
		wp_safe_redirect(esc_url_raw(add_query_arg('omsg', 'logout_success', $this->redirect_to_url())));
		exit;

	}

	// Translate "omsg" value into overlay message
	function logout_overlay_message($omsg) {
		if($omsg != 'logout_success') {
			return $omsg;
		}

		return array(
			'type' => 'success',
			'title' => __('Good bye', 'th23-user-management'),
			'text' => __('You have been logged out successfully - hope to see you soon again', 'th23-user-management')
		);

	}

	// === TROUBLE ===

	// Init trouble functionality
	function trouble_init() {
		// All hooks here early (= 5) - in case we have trouble, we want to ensure to do/show troubleshooting
		add_action($this->plugin . '_do_early', array(&$this, 'trouble_do_early'), 5);
		add_action($this->plugin . '_prepare_output', array(&$this, 'trouble_prepare_output'), 5);
	}

	// Fix cookie troubles
	function trouble_do_early() {
		// Has anything else already been done?
		if(!empty($this->data['action_done'])) {
			return;
		}
		// Do we have something to do - and is it allowed to do this?
		if(isset($_POST['trouble']) && isset($_POST['submit'])) {
			$this->data['action_done'] = true; // not really necessary, due to "exit"...but for consistency
			wp_clear_auth_cookie();
			wp_destroy_current_session();
			wp_safe_redirect(esc_url_raw($this->user_management_url('cleared')));
			exit;
		}
		elseif(isset($_REQUEST['cleared'])) {
			$this->data['action_done'] = true; // not really necessary, as we got redirected here...but to avoid somebody messing around
			$this->data['msg'][] = array('type' => 'success', 'text' => __('All cookies have been cleared - please login again', 'th23-user-management'));
			// handle as normal login attempt going forward - but not as submitted
			unset($_REQUEST['cleared']);
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('cleared'));
		}
	}

	// Prepare trouble page
	function trouble_prepare_output() {
		// Any output yet defined?
		if(!empty($this->data['page_content'])) {
			return;
		}
		// Are we asked to show something?
		if(!isset($_REQUEST['trouble'])) {
			return;
		}

		$this->data['page_title'] = __('Troubleshooting', 'th23-user-management');

		$html = '<p class="message">' . __('This website is relying on cookies stored on your computer to remember who is logged in.', 'th23-user-management') . '</p>';
		$html .= '<p class="message">' . __('Unfortunately there might be occasions, when this memory can get confused, e.g. upon logins from different computers under certain circumstances. This might result in trouble logging in or the submission of information being denied with an &quot;invalid request&quot; error.', 'th23-user-management') . '</p>';
		$html .= '<p class="message">' . __('To fix this, simply clear all cookies from this site out of your browser by using the button below and log in again afterwards.', 'th23-user-management') . '</p>';
		$html .= '<form name="troubleform" id="troubleform" action="' . esc_url($this->user_management_url()) . '" method="post">';
		$html .= '<p>';
		$html .= '<input type="submit" name="submit" id="submit" class="button-primary" value="' . esc_attr(__('Delete cookies', 'th23-user-management')) . '" />';
		$html .= '<input type="hidden" name="trouble" value="1" />';
		$html .= '</p>';
		$html .= '</form>';
		$html .= '<div class="th23-user-management-links"><ul>';
		$html .= '<li><a href="' . esc_url($this->user_management_url('login')) . '">' . __('Log in', 'th23-user-management') . '</a></li>';
		if(get_option('users_can_register')) {
			$html .= '<li><a href="' . esc_url($this->user_management_url('register')) . '">' . __('Register', 'th23-user-management') . '</a></li>';
		}
		$html .= '<li><a href="' . esc_url(wp_lostpassword_url()) . '">' . __('Lost your password?', 'th23-user-management') . '</a></li>';
		$html .= '</ul></div>';

		$this->data['page_content'] = $html;

	}

	// === REGISTER ===

	// Init register functionality
	function register_init() {
		add_filter('register_url', array(&$this, 'register_url'));
		add_filter('is_email', array(&$this, 'is_email'), 10, 3);
		add_action($this->plugin . '_do_normal', array(&$this, 'register_do_normal'));
		add_action($this->plugin . '_prepare_output', array(&$this, 'register_prepare_output'));
	}

	// Modify register_url used by WordPress
	function register_url() {
		return $this->user_management_url('register');
	}

	// Prevent "&" character being in e-mail addresses - this leads to errors upon storage, already in standard WordPress installation
	function is_email($return, $email, $context) {
		if(!empty($context)) {
			return $return;
		}
		return (strpos($email, '&') === false) ? $email : false;
	}

	// Check given email address - here more conistent than done in wp_create_user function
	// Note: This function will be used upon registration and upon change of e-mail via edit profile
	function check_email($email) {
		if(empty($email)) {
			$this->data['msg']['empty_email'] = array('type' => 'error', 'text' => __('Please enter your e-mail address', 'th23-user-management'));
			return false;
		}
		elseif(!is_email($email)) {
			$this->data['msg']['invalid_email'] = array('type' => 'error', 'text' => __('This e-mail address is invalid, please enter your valid e-mail address', 'th23-user-management'));
			return false;
		}
		elseif(email_exists($email)) {
			$this->data['msg']['email_exists'] = array('type' => 'error', 'text' => __('This e-mail address is already registered, please provide another one', 'th23-user-management'));
			return false;
		}
		return true;
	}

	// Execute registration attempt
	function register_do_normal() {
		// Has anything else already been done?
		if(!empty($this->data['action_done'])) {
			return;
		}
		// Do we have something to do - and is it allowed to do this?
		if(!isset($_REQUEST['register']) || !isset($_REQUEST['submit']) || !get_option('users_can_register')) {
			return;
		}
		$this->data['action_done'] = true;

		// Validate nonce - abort registration if failed
		if(!wp_verify_nonce($_POST[$this->slug . '-register-nonce'], $this->plugin . '_register')) {
			unset($_POST);
			$this->data['msg'][] = array('type' => 'error', 'text' => __('Invalid request - please use the form below to register', 'th23-user-management'));
			return;
		}

		// Check wanted username - here more conistent than done in wp_create_user function
		if(empty($_POST['user_login'])) {
			$this->data['msg']['empty_username'] = array('type' => 'error', 'text' => __('Please enter a username', 'th23-user-management'));
		}
		elseif(!validate_username($_POST['user_login'])) {
			$this->data['msg']['invalid_username'] = array('type' => 'error', 'text' => __('This username is invalid as it contains not allowed characters, please use another one', 'th23-user-management'));
		}
		elseif(username_exists($_POST['user_login'])) {
			$this->data['msg']['username_exists'] = array('type' => 'error', 'text' => __('This username is already registered, please choose another one', 'th23-user-management'));
		}

		// Un-salt user_email field
		$_POST['user_email'] = $_POST[md5('user_email_' . $_POST['salt'])];

		// Check given e-mail address - see separte function above, as also used upon change via edit profile
		$this->check_email($_POST['user_email']);

		// Get new password - auto-generated or choosen by user
		$new_user_pass = apply_filters($this->plugin . '_register_validate_password', wp_generate_password(12, false));

		// Check if user was required to solve a captcha and did so successfully - see PRO class
		do_action($this->plugin . '_register_captcha_validate');

		// Verify terms
		if($this->options['terms']) {
			if(!isset($_POST['terms']) || empty($_POST['terms'])) {
				$terms = (empty($title = get_option('th23_terms_title'))) ? __('Terms of Usage', 'th23-user-management') : $title;
				/* translators: %s: title of terms & conditions page, as defined by admin */
				$this->data['msg']['terms_not_accepted'] = array('type' => 'error', 'text' => sprintf(__('Please accept the %s, agree with processing your data and the usage of cookies', 'th23-user-management'), $terms));
			}
		}

		// Allow filtering of errors found upon user registration - eg used by th23 Subscribe plugin
		// note: requires own filter (th23_user_management_registration_errors) not default WP one due to different error object used
		$this->data['msg'] = apply_filters($this->plugin . '_registration_errors', $this->data['msg'], $_POST['user_login'], $_POST['user_email']);

		// Create the new user
		if(empty($this->data['msg'])) {
			$new_user_role = apply_filters($this->plugin . '_register_user_role', get_option('default_role'));
			$new_user_id = wp_insert_user(array('user_login' => $_POST['user_login'], 'user_email' => $_POST['user_email'], 'user_pass' => $new_user_pass, 'role' => $new_user_role));
			if(is_wp_error($new_user_id)) {
				$this->data['msg'][] = array('type' => 'error', 'text' => __('You could not be registered due to a server error. Please try again - if the error persists contact the administrator of this site', 'th23-user-management'));
			}
			else {
				$new_user = get_userdata($new_user_id);
			}
		}

		// Send required mails and set some basic paramenters for the new user
		if(empty($this->data['msg'])) {
			do_action($this->plugin . '_register_send_mail', $new_user, $new_user_pass);
		}
		if(empty($this->data['msg'])) {
			wp_new_user_notification($new_user_id); // sends ONLY notification mail to admin about new registration
			update_user_option($new_user_id, 'default_password_nag', true, true);
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			/* translators: mail title to new users - blog name to be parsed in */
			$mail_title = sprintf(__('[%s] Welcome / Your username and password', 'th23-user-management'), $blogname);
			/* translators: mail body to new users - 1: blog name, 2: user login, 3: user password, 4: login link */
			$mail_text = sprintf(__('Welcome to %1$s and thanks for joining!

Your username: %2$s
Your password: %3$s

You can now login using your username and password above. Therefore please visit the following address:
%4$s', 'th23-user-management'), $blogname, $new_user->user_login, $new_user_pass, esc_url_raw($this->user_management_url('login&log=' . esc_attr($new_user->user_login))));
			if(!wp_mail($new_user->user_email, $mail_title, $mail_text)) {
				$this->data['msg'][] = array('type' => 'error', 'text' => __('You have been registered, but the required mail could not be sent due to a server error. Please contact the administrator of this site', 'th23-user-management'));
			}
			else {
				$this->data['msg'][] = array('type' => 'success', 'text' => __('You have been registered successfully - please check your inbox for initial password to login using the form below', 'th23-user-management'));
			}
			// Show the login page - with the above defined message
			unset($_REQUEST['register']);
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('register'));
		}

	}

	// Prepare registration page
	function register_prepare_output() {
		// Any output yet defined?
		if(!empty($this->data['page_content'])) {
			return;
		}
		// Are we asked to show something?
		if(is_user_logged_in() || !isset($_REQUEST['register'])) {
			return;
		}

		$this->data['page_title'] = __('Registration', 'th23-user-management');

		if(get_option('users_can_register')) {
			$html = '<p class="message">' . __('Please fill out the fields below to register.', 'th23-user-management') . '</p>';
			$html .= apply_filters($this->plugin . '_register_explanation_html', '<p class="message">' . __('An initial password will be sent to the given e-mail address.', 'th23-user-management') . '</p>');
			$html .= '<form name="registerform" id="registerform" action="' . esc_url($this->user_management_url('register')) . '" method="post">';
			$html .= '<p><span class="th23-user-management-input-wrap">';
			$html .= '<label for="user_login">' . __('Username', 'th23-user-management') . '</label>';
			$user_login = (isset($_POST['user_login'])) ? esc_attr(wp_unslash($_POST['user_login'])) : '';
			$html .= '<input type="text" name="user_login" id="user_login" class="input" value="' . $user_login . '" size="20" />';
			$html .= '</span></p>';
			// dev: add on the spot check for validity of email?
			$html .= '<p><span class="th23-user-management-input-wrap">';
			$salt = wp_generate_password(10, false);
			$salted_user_email = md5('user_email_' . $salt);
			$html .= '<label for="' . $salted_user_email . '">' . __('E-mail', 'th23-user-management') . '</label>';
			$user_email = (isset($_POST['user_email'])) ? esc_attr(wp_unslash($_POST['user_email'])) : '';
			$html .= '<input type="text" name="' . $salted_user_email . '" id="' . $salted_user_email . '" class="input" value="' . $user_email . '" size="20" />';
			$html .= '</span></p>';
			$html .= apply_filters($this->plugin . '_register_options_html', '');
			if($this->options['terms']) {
				$terms = (empty($title = get_option('th23_terms_title'))) ? __('Terms of Usage', 'th23-user-management') : $title;
				$terms = ($url = get_option('th23_terms_url')) ? '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($terms) . '</a>' : esc_html($terms);
				$html .= '<p>';
				/* translators: %s: link with/or title to page with terms & conditions, as defined by admin */
				$html .= '<input name="terms" type="checkbox" id="terms" value="ok"' . (!empty($_POST['terms']) ? ' checked="checked"' : '') . ' /> <label for="terms">' . sprintf(__('I accept the %s, agree with processing my data and the usage of cookies', 'th23-user-management'), $terms) . '</label>';
				$html .= '</p>';
			}
			$html .= apply_filters($this->plugin . '_register_captcha_html', '');
			$html .= '<p class="submit">';
			$html .= '<input type="submit" name="submit" id="submit" class="button-primary" value="' . esc_attr(__('Register', 'th23-user-management')) . '" />';
			$html .= '<input type="hidden" name="salt" value="' . esc_attr($salt) . '" />';
			$html .= wp_nonce_field($this->plugin . '_register', $this->slug . '-register-nonce', true, false);
			$html .= '</p>';
			$html .= '</form>';
		}
		else {
			$html = '<div class="th23-message th23-user-management-message error"><strong>' . __('Error', 'th23-user-management') . '</strong>: ' . __('User registration is currently not allowed', 'th23-user-management') . '</div>';
		}
		$html .= '<div class="th23-user-management-links"><ul>';
		$html .= '<li><a href="' . esc_url($this->user_management_url('login')) . '">' . __('Log in', 'th23-user-management') . '</a></li>';
		$html .= '<li><a href="' . esc_url(wp_lostpassword_url()) . '">' . __('Lost your password?', 'th23-user-management') . '</a></li>';
		$html .= '<li><a href="' . esc_url($this->user_management_url('trouble')) . '">' . __('Trouble registering?', 'th23-user-management') . '</a></li>';
		$html .= '</ul></div>';

		$this->data['page_content'] = $html;

	}

	// === EDIT PROFILE ===

	// Init edit profile functionality
	function edit_profile_init() {
		add_action($this->plugin . '_do_normal', array(&$this, 'edit_profile_session_logout_do_normal'));
		add_action($this->plugin . '_prepare_output', array(&$this, 'edit_profile_prepare_output'), 15); // later than others, so it can be the fallback for logged-in users
	}

	// Destroy all other user sessions - except the current one, eg to invalidate sessions on lost phone, public computer, ...
	function edit_profile_session_logout_do_normal() {
		// Has anything else already been done?
		if(!empty($this->data['action_done'])) {
			return;
		}
		// Do we have something to do - and is it allowed to do this?
		if(!is_user_logged_in() || !isset($_REQUEST['editprofile']) || !isset($_REQUEST['session'])) {
			return;
		}
		$this->data['action_done'] = true;

		// Validate nonce - abort logout if failed
		if(!wp_verify_nonce($_POST[$this->slug . '-edit-profile-nonce'], $this->plugin . '_edit_profile')) {
			unset($_POST);
			$this->data['msg'][] = array('type' => 'error', 'text' => __('Invalid request - please use the form below for any actions', 'th23-user-management'));
			return;
		}

		// Let's do it!
		wp_destroy_other_sessions();
		$this->data['msg'][] = array('type' => 'success', 'text' => __('You have been logged out of all other sessions - except the one on this browser', 'th23-user-management'));
		$this->data['msg'][] = array('type' => 'info', 'text' => __('In case you did any changes on your profile below, these have not yet been saved - please do so via the buttons at the bottom', 'th23-user-management'));

	}

	// Prepare edit profile page
	function edit_profile_prepare_output() {
		// Any output yet defined?
		if(!empty($this->data['page_content'])) {
			return;
		}
		// Are we asked to show something?
		// Do not show, if user is NOT logged in - otherwise no check, as we are the fallback
		if(!is_user_logged_in()) {
			return;
		}

		$this->data['page_title'] = __('Your Profile', 'th23-user-management');

		$current_user = wp_get_current_user();

		if(current_user_can('edit_user', $current_user->ID)) {
			// Note: Editing user profiles is only available in the professional version of this plugin!
			$pro = apply_filters($this->plugin . '_edit_profile_disabled', ' disabled="disabled"');
			$html = '<p class="message">' . __('Modify your profile and settings for this page below.', 'th23-user-management') . '</p>';
			$html .= '<form name="profileform" id="profileform" action="' . esc_url($this->user_management_url()) . '" enctype="multipart/form-data" method="post">';
			$html .= '<h3>' . __('Name', 'th23-user-management') . '</h3>';

			$html .= '<p>';
			$html .= '<span class="th23-user-management-input-wrap">';
			$html .= '<label for="user_login">' . __('Username', 'th23-user-management') . '</label>';
			$html .= '<input type="text" name="user_login" id="user_login" class="input" value="' . esc_attr($current_user->user_login) . '" size="20" disabled="disabled" />';
			$html .= '</span>';
			$html .= '<span class="description">' . __('Can not be changed', 'th23-user-management') . '</span>';
			$html .= '</p>';
			$html .= '<p><span class="th23-user-management-input-wrap">';
			$html .= '<label for="first_name">' . __('First Name', 'th23-user-management') . '</label>';
			$html .= '<input type="text" name="first_name" id="first_name" class="input" value="' . esc_attr((isset($_POST['first_name'])) ? wp_unslash($_POST['first_name']) : $current_user->first_name) . '" size="20"' . $pro . ' />';
			$html .= '</span></p>';
			$html .= '<p><span class="th23-user-management-input-wrap">';
			$html .= '<label for="last_name">' . __('Last Name', 'th23-user-management') . '</label>';
			$html .= '<input type="text" name="last_name" id="last_name" class="input" value="' . esc_attr((isset($_POST['last_name'])) ? wp_unslash($_POST['last_name']) : $current_user->last_name) . '" size="20"' . $pro . ' />';
			$html .= '</span></p>';
			$html .= '<p>';
			$html .= '<span class="th23-user-management-input-wrap">';
			$html .= '<label for="nickname">' . __('Nickname', 'th23-user-management') . '</label>';
			$html .= '<input type="text" name="nickname" id="nickname" class="input" value="' . esc_attr((isset($_POST['nickname'])) ? wp_unslash($_POST['nickname']) : $current_user->nickname) . '" size="20"' . $pro . ' />';
			$html .= '</span>';
			$html .= '<span class="description">' . __('Required', 'th23-user-management') . '</span>';
			$html .= '</p>';
			$html .= '<p>';
			$html .= '<span class="th23-user-management-input-wrap">';
			$html .= '<label for="display_name">' . __('Display Name', 'th23-user-management') . '</label>';
			$html .= '<select name="display_name" id="display_name"' . $pro . '>';
			$display_name_ary = array();
			$display_name_ary[] = $current_user->user_login;
			if(!empty($current_user->first_name)) {
				$display_name_ary[] = $current_user->first_name;
			}
			if(!empty($current_user->last_name)) {
				$display_name_ary[] = $current_user->last_name;
			}
			if(!empty($current_user->first_name) && !empty($current_user->last_name)) {
				$display_name_ary[] = $current_user->first_name . ' ' . $current_user->last_name;
				$display_name_ary[] = $current_user->last_name . ' ' . $current_user->first_name;
			}
			$display_name_ary[] = $current_user->nickname;
			$display_name_ary = array_unique($display_name_ary);
			foreach($display_name_ary as $display_name) {
				$selected = (((isset($_POST['display_name'])) ? wp_unslash($_POST['display_name']) : $current_user->display_name) == $display_name) ? ' selected="selected"' : '';
				$html .= '   <option value="' . esc_attr($display_name) . '"' . $selected . '>' . esc_html($display_name) . '</option>';
			}
			$html .= '</select>';
			$html .= '</span>';
			$html .= '</p>';
			$html .= '<h3>' . __('Contact', 'th23-user-management') . '</h3>';
			$html .= '<p>';
			$html .= '<span class="th23-user-management-input-wrap">';
			$html .= '<label for="email">' . __('E-Mail', 'th23-user-management') . '</label>';
			$html .= '<input type="text" name="email" id="email" class="input" value="' . esc_attr((isset($_POST['email'])) ? wp_unslash($_POST['email']) : $current_user->user_email) . '" size="20"' . $pro . ' />';
			$html .= '</span>';
			$html .= '<span class="description">' . __('Required, change needs to be confirmed', 'th23-user-management') . '</span>';
			$pending_email = get_user_meta($current_user->ID, $this->plugin . '_edit_profile_mail_revalidation', true);
			if($pending_email) {
				$html .=  '<span class="th23-message th23-user-management-message info"><strong>' . __('Note', 'th23-user-management') . '</strong>: ';
				/* translators: parse in new mail address */
				$html .= sprintf(__('The change of your e-mail to %s is pending - please check your new e-mail inbox for validation', 'th23-user-management'), '<code>' . $pending_email . '</code>') . '</span>';
			}
			$html .= '</p>';
			$html .= '<p><span class="th23-user-management-input-wrap">';
			$html .= '<label for="user_url">' . __('Website', 'th23-user-management') . '</label>';
			$html .= '<input type="text" name="user_url" id="user_url" class="input" value="' . esc_attr((isset($_POST['user_url'])) ? wp_unslash($_POST['user_url']) : $current_user->user_url) . '" size="20"' . $pro . ' />';
			$html .= '</span></p>';
			foreach(_wp_get_user_contactmethods($current_user) as $method => $title) {
				$html .= '<p><span class="th23-user-management-input-wrap">';
				$html .= '<label for="' . $method . '">' . $title . '</label>';
				$html .= '<input type="text" name="' . $method . '" id="' . $method . '" class="input" value="' . esc_attr((isset($_POST[$method])) ? wp_unslash($_POST[$method]) : $current_user->$method) . '" size="20"' . $pro . ' />';
				$html .= '</span></p>';
			}
			$html .= '<h3>' . __('Info', 'th23-user-management') . '</h3>';
			$html .= apply_filters($this->plugin . '_edit_profile_info_html', '');
			$html .= '<p>';
			$html .= '<span class="th23-user-management-input-wrap">';
			$html .= '<label for="description">' . __('Biographical Info', 'th23-user-management') . '</label>';
			$html .= '<textarea name="description" id="description" rows="5" cols="30"' . $pro . '>' . ((isset($_POST['description'])) ? wp_unslash($_POST['description']) : $current_user->description) . '</textarea>';
			$html .= '</span>';
			$html .= '<span class="description">' . __('Information about yourself provided here might be shown publicly', 'th23-user-management') . '</span>';
			$html .= '</p>';
			$html .= '<h3>' . __('Settings', 'th23-user-management') . '</h3>';
			if(empty($pro)) {
				$html .= '<p>';
				$html .= '<span class="th23-user-management-input-wrap">';
				$html .= '<label for="pass1">' . __('New password', 'th23-user-management') . '</label>';
				$html .= '<input type="password" name="pass1" id="pass1" class="input" value="" size="20" autocomplete="off" />';
				$html .= '</span>';
				$html .= '<span class="description">' . __('Leave empty, if you do NOT want to change your password', 'th23-user-management') . '</span>';
				$html .= '</p>';
				$html .= '<p><span class="th23-user-management-input-wrap">';
				$html .= '<label for="pass2">' . __('Confirm new password', 'th23-user-management') . '</label>';
				$html .= '<input type="password" name="pass2" id="pass2" class="input" value="" size="20" autocomplete="off" />';
				$html .= '</span></p>';
				$html .= '<p id="pass-strength-indicator">';
				$html .= '<span class="label">' . __('Password strength indicator', 'th23-user-management') . '</span>';
				/* translators: shortcut for "not applicable" */
				$html .= '<input id="pass-strength-result" type="text" class="input" value="' . esc_attr(__('n/a', 'th23-user-management')) . '" size="20" disabled="disabled">';
				$html .= '<span class="description">' . wp_get_password_hint() . '</span>';
				$html .= '</p>';
			}
			$html .= '<p>';
			$sessions = WP_Session_Tokens::get_instance($current_user->ID);
			if(count($sessions->get_all()) > 1) {
				$html .= '<button type="submit" name="session" id="session" class="button button-secondary">' . esc_attr(__('Log Out of All Other Sessions', 'th23-user-management')) . '</button>';
				$html .= '<span class="description">' . __('Left your account logged in at a public computer? Lost your phone? This will log you out everywhere except your current browser.', 'th23-user-management') . '</span>';
			}
			else {
				$html .= '<button type="submit" name="session" id="session" class="button button-secondary" disabled="disabled">' . __('Log Out of All Other Sessions', 'th23-user-management') . '</button>';
				$html .= '<span class="description">' . esc_attr(__('You are only logged in at this location.', 'th23-user-management')) . '</span>';
			}
			$html .= '</p>';
			if($this->options['admin_bar'] == 'admin_access') {
				$this->options['admin_bar'] = $this->options['admin_access'];
			}
			if($this->options['admin_bar'] == 'default' || current_user_can($this->options['admin_bar'])) {
				$html .= '<p class="show-admin-bar">';
				if(isset($_POST['show_admin_bar_front'])) {
					$checked = (!empty($_POST['show_admin_bar_front'])) ? ' checked="checked"' : '';
				}
				else {
					$checked = ($current_user->show_admin_bar_front == 'true') ? ' checked="checked"' : '';
				}
				$html .= '<input type="checkbox" name="show_admin_bar_front" id="show_admin_bar_front" value="true"' . $checked . $pro . ' /><label for="show_admin_bar_front">' . __('Show Admin Bar when viewing site', 'th23-user-management') . '</label>';
				$html .= '</p>';
			}
			if(empty($pro)) {
				$html .= '<div class="submit">';
				$html .= '<input type="submit" name="submit" id="submit" class="button button-primary" value="' . esc_attr(__('Save', 'th23-user-management')) . '" />';
				$html .= '<input type="submit" name="cancel" id="cancel" class="button button-secondary" value="' . esc_attr(__('Cancel', 'th23-user-management')) . '" />';
				$html .= '</div>';
			}
			else {
				$html .= '<div class="th23-user-management-form-note"><strong>' . __('Note', 'th23-user-management') . '</strong>: ' . sprintf(__('To edit your profile please visit the <a href="%s">Profile section in the admin area</a>'), esc_url(admin_url() . 'profile.php')) . '</div>';
			}
			$html .= '<input type="hidden" name="editprofile" value="1" />';
			$html .= wp_nonce_field($this->plugin . '_edit_profile', $this->slug . '-edit-profile-nonce', true, false);
			$html .= '</form>';
		}
		else {
			$html = '<div class="th23-message th23-user-management-message error"><strong>' . __('Error', 'th23-user-management') . '</strong>: ' . __('You are not allowed to edit your profile', 'th23-user-management') . '</div>';
		}

		$this->data['page_content'] = $html;

	}

}

// === WIDGET ===

class th23_user_management_widget extends WP_Widget {

	function __construct() {

		global $th23_user_management;
		$master = $th23_user_management;

		parent::__construct(false, $name = 'th23 User Management', array('description' => __('Displays registration, login, logout and user management options', 'th23-user-management')));

	}

	// Ensure PHP <5 compatibility
	function th23_user_management_widget() {
		self::__construct();
	}

	function widget($args, $instance) {

		global $th23_user_management;
		$master = $th23_user_management;

		extract($args);

		echo $before_widget;
		echo $before_title;

		if(is_user_logged_in()) {

			$current_user = wp_get_current_user();
			echo $current_user->display_name;

			echo $after_title;

			echo '<div class="widget_meta"><ul class="' . $master->slug . '">';
			echo '<li class="edit-profile"><a href="' . esc_url($master->user_management_url()) . '">' . __('Edit profile', 'th23-user-management') . '</a></li>';
			// Allow for integration of th23 Subscribe plugin - showing link to modify subscriptions
			do_action($master->plugin . '_widget_profile_link', $master->user_management_url(), '<li>%s</li>');
			if($master->options['admin_access'] == 'default' || current_user_can($master->options['admin_access'])) {
				echo '<li class="dashboard"><a href="' . esc_url(admin_url()) . '">' . __('Dashboard', 'th23-user-management') . '</a></li>';
				if(current_user_can('edit_posts')) {
					echo '<li class="write-post"><a href="' . esc_url(admin_url() . 'post-new.php') . '">' . __('Write post', 'th23-user-management') . '</a></li>';
				}
			}
			echo '<li class="log-out"><a href="' . esc_url($master->logout_url()) . '">' . __('Log out', 'th23-user-management') . '</a></li>';
			echo '</div></ul>';

		}
		else {

			echo (!is_page($master->options['page_id'])) ? __('Login', 'th23-user-management') : __('User Management', 'th23-user-management');

			echo $after_title;

			echo '<div class="widget_meta"><ul class="' . $master->slug . '">';
			// Show full login form in widget, if we are not on the user management page (avoiding two user management forms shown at the same time)
			if(!is_page($master->options['page_id'])) {
				echo '<li><form action="' . esc_url($master->user_management_url('login')) . '" method="post" class="th23-user-management-widget-form">';
				echo '<div><span class="th23-user-management-input-wrap"><label for="log_widget">' . __('User', 'th23-user-management') . '</label><input type="text" name="log" id="log_widget" size="20" placeholder="' . esc_attr(__('User', 'th23-user-management')) . '" /></span></div>';
				echo '<div><span class="th23-user-management-input-wrap"><label for="pwd_widget">' . __('Password', 'th23-user-management') . '</label><input type="password" name="pwd" id="pwd_widget" size="20" placeholder="' . esc_attr(__('Password', 'th23-user-management')) . '" /></span></div>';
				$redirect = (is_page($master->options['page_id'])) ? home_url() : $master->get_current_url();
				echo '<input type="submit" name="submit" value="' . esc_attr(__('Log in', 'th23-user-management')) . '" class="button" /><input name="rememberme" id="rememberme_widget" type="checkbox" value="forever" /><label for="rememberme_widget">' . __('Remember me', 'th23-user-management') . '</label><input type="hidden" name="redirect_to" value="' . esc_attr($redirect) . '"/>';
				wp_nonce_field('th23_user_management_login', 'th23-user-management-login-nonce');
				echo '</form></li>';
			}
			else {
				echo '<li class="log-in"><a href="' . esc_url($master->user_management_url()) . '">' . __('Log in', 'th23-user-management') . '</a></li>';
			}
			if(get_option('users_can_register')) {
				echo '<li class="register"><a href="' . esc_url($master->user_management_url('register')) . '">' . __('Register', 'th23-user-management') . '</a></li>';
			}
			echo '<li class="lost-password"><a href="' . esc_url(wp_lostpassword_url()) . '">' . __('Lost your password?', 'th23-user-management') . '</a></li>';
			echo '<li class="trouble"><a href="' . esc_url($master->user_management_url('trouble')) . '">' . __('Trouble?', 'th23-user-management') . '</a></li>';
			echo '</ul></div>';

		}

		echo $after_widget;

	}

}
add_action('widgets_init', create_function('', 'return register_widget("th23_user_management_widget");'));

// === INITIALIZATION ===

// Define our plugin basis
define('TH23_USER_MANAGEMENT_BASENAME', plugin_basename(__FILE__));
define('TH23_USER_MANAGEMENT_BASEDIR', '/' . str_replace('/' . basename(__FILE__), '', TH23_USER_MANAGEMENT_BASENAME));

// Load additional PRO class, if it exists
if(file_exists(WP_PLUGIN_DIR . TH23_USER_MANAGEMENT_BASEDIR . '/th23-user-management-pro.php')) {
	require(WP_PLUGIN_DIR . TH23_USER_MANAGEMENT_BASEDIR . '/th23-user-management-pro.php');
}
// Mimic PRO class, if it does not exist
if(!class_exists('th23_user_management_pro')) {
	class th23_user_management_pro extends th23_user_management {
		function __construct() {
			parent::__construct();
		}
		// Ensure PHP <5 compatibility
		function th23_user_management_pro() {
			self::__construct();
		}
	}
}

// Load additional admin class, if required...
if(is_admin() && file_exists(WP_PLUGIN_DIR . TH23_USER_MANAGEMENT_BASEDIR . '/th23-user-management-admin.php')) {
	require(WP_PLUGIN_DIR . TH23_USER_MANAGEMENT_BASEDIR . '/th23-user-management-admin.php');
	$th23_user_management = new th23_user_management_admin();
}
// ...or initiate plugin via (mimiced) PRO class
else {
	$th23_user_management = new th23_user_management_pro();
}

?>
