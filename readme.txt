=== Plugin Name ===
Contributors: th23
Donate link: http://th23.net/th23-user-management
Tags: user management, frontend, customized, styled, login, logout, wp-login, admin access, register, registration, sign-up, chose password, user password, user approval, approve, edit profile, manage profile, user profile, captcha, recaptcha, spam, bots
Requires at least: 4.2.0
Tested up to: 5.2.0
Stable tag: 2.4.0
License: GPLv2 only
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Styled user management for login, register, user profile... - optional: user chosen passwords, e-mail validation, admin approval, reCaptcha and more

== Description ==

All user management activities like login, user profile, register, lost password, etc. can be done via the **themed frontend** of your website. Access for user groups to unstyled **admin area can be restricted** and **`wp-login.php` can be disabled**. Users will only see the nicely styled side of your page :-)

The plugin is very flexible, allowing for further modifications and extensions to hook into, displaying further user management activities on the frontend of your website.

Additional options to enhance user experience further are available as a *Professional extension* of this plugin and include:

* **All user management actions available on frontend** styled according to theme - including profile changes, lost password, reset password
* **Access to the unstyled admin area can be restricted** based on user groups - `wp-login.php` can be disabled completely
* **User chosen password upon registration** option available - including initial e-mail validation
* **Admin approval for new users** option available - before user can login
* **Use reCaptcha against spam and bots** upon registration, lost password and login - after specified amount of unsuccessful attempts
* Introduction of e-mail re-validation upon changes of address

In case you want to see the plugin in action, feel free to visit the [authors website](http://th23.net/).

For support, feedback and suggestions, please visit our [FAQ and support website](http://th23.net/th23-user-management-faq-and-support/)!

== Installation ==

To install th23 User Management, follow these steps:

1. Download and unzip the th23 User Management plugin
1. Upload the entire `th23-user-management/` directory to the `/wp-content/plugins/` directory
1. Activate th23 User Management plugin through the Plugins menu in the WordPress admin area
1. Configure th23 User Managemet in the WordPress admin area, select Settings and th23 User Management for an overview on options
1. Add the th23 User Managemet widget for easy access by your users, in the WordPress admin area go to Appearance / Widgets, drag the th23 User Management widget from "Available Widgets" on the left to a selected "Widget Area" on the right
1. (optional) For approval of users go to the Users section in WordPress the admin area, select Pending users and chose Approve or Delete

That is it - your users will now have the chance to do user related actions directly on your styled page!

== Frequently Asked Questions ==

For support, feedback and suggestions, please visit our [FAQ and support website](http://th23.net/th23-user-management-faq-and-support/)!

== Screenshots ==

1. Seamless inclusion of user management actions - here user profile page, including a dedicated widget for direct access by users via frontend (here on Twenty Fifteen theme)
2. Widget with option to login directly - without leaving current page (here on Twenty Fifteen theme)
3. Login messages displayed on current / main page via overlay messages (here on Twenty Fifteen theme)
4. Nicely themed user registration page - optionally including user chosen password and increased security against bots/ spam through captcha (here on Twenty Fifteen theme)
5. User registration page and user management widget adapting to chosen theme (here on D5 Colorful theme)
6. Login page and widget nicely styled for whatever theme you decide for...and of course further customizable via CSS (here on Twenty Fourteen, Modern, Roda, Sydney and a customized Twenty Eleven theme)
7. Select your mobile theme and the user management will be available - nicely styled as your users can expect (here on Twenty Fifteen and Colors theme)
8. The plugin is highly customizable - most options are part of the separately available Professional extension
9. The plugin embedds admin approvals for new registrations via the standard users screen - this option part of the separately available Professional extension

== Changelog ==

= v2.4.0 =
* [Enhancement - Basic] Option to ask for approval of terms & conditions before registering as new user eg to comply with new data privacy law - option working as a "shared" admin option to specify title/link to legal information page once across all th23 plugins and themes
* [Enhancement - Basic/Pro] Enable smooth integration with th23 Subscribe plugin, th23 Social plugin, th23 Travel theme and th23 Thorsten theme - via introduction of various filter, hooks and checks
* [Enhancement - Basic] Show dates for user "registered", "last login" and "last visit" as columns in admin user list
* [Enhancement - Basic/Pro] Allow for better theming eg input fields including label and placeholders, removing fixed spacings as separators, user management page title
* [Enhancement - Basic/Pro] Added German translation for whole plugin
* [Fix - Basic] Ensure compatability with WooCommerce - lost password URL should be defined by this plugin
* [Fix - Basic/Pro] Some spelling mistakes

= v2.3.0 =
* [Enhancement/Fix - Basic/PRO] Prepare for translations via translate.wordpress.org repository and tools - use "th23-user-management" as Text Domain in plugin header, replace $this->plugin in translation functions with hardcoded "th23-user-management" string, include "/lang" as Domain Path in plugin header, adjust load_plugin_textdomain function call accordingly and optimize language strings with fewer tags and added translator comments
* [Enhancement - PRO only] Optimize user notification mail after admin approval ie simplify structure, add personalized password reset link
* [Enhancement - PRO only] Improve tooltip for Captcha explanation, removing link and only use spans for better handling on mobile devices
* [Enhancement - Basic/PRO] Add version number handling to script and style loading, ensuring loading latest version in browsers
* [Enhancement - Basic/PRO] Adapt support URLs to new structure on th23.net
* [Enhancement - Basic/PRO] Add classes to each link in the widget, to enable specific styling if required
* [Enhancement - Basic/PRO] Better structure plugin related links on plugin overview page in admin area
* [Fix - PRO only] Properly handle pre-filling mail/ name field after submitting password reset request
* [Fix - PRO only] Include jQuery JS into dependency upon including password strength meter
* [Change - PRO only] Admin triggered change of mail address does not require user confirm, but only triggers notification to old and new mail address
* [Enhancement - PRO only] Optimize sending password reset message upon changes done via admin area
* [Fix - Basic/PRO] Corrected spelling of "registered" in some occurances
* [Fix - Basic/PRO] Ensure proper handling of "redirect_to" parameter after login
* [Fix - Basic/PRO] Exclude user management page from search results
* [Fix - Basic/PRO] Prevent "jumping" settings fields upon opening sub-settings
* [Fix - Basic/PRO] Harmonize classes for "omsg" and "message" across th23 plugins, for easier styling via theme
* [Fix - Basic/PRO] Replace usage of deprecated function "get_currentuserinfo" with "wp_get_current_user"

= v2.2.0 =
* [Enhancement - Basic/PRO] Change overlay message approach - updated CSS, less JS, more depending on (themable) class styling
* [Enhancement/Fix - PRO only] Adjust handling of user creation/ password and e-mail changes to process in WP 4.3 and later - esp proper user information upon changes triggered by administrator
* [Enhancement - Basic/PRO] Use "placeholder" tag instead of description and "br" in widget
* [Fix - PRO only] Add redirect after password change, to ensure user can log in again directly
* [Fix - Basic/PRO] Remove unnecessary line breaks at the end of mail messages
* [Fix - Basic/PRO] Adapt admin settings page header and plugin notice to changed admin CSS in WP 4.3

= v2.1.0 =
* [Enhancement] Changed class handling / constructors to php5+ style (__construct) for compliance with WordPress standards
* [Enhancement] Added danish translation - thanks to Rasmus

= v2.0.1 =
* [Enhancement] Adapted widget HTML to take up CSS styling from current theme in more cases easily - added "widget_meta" class
* [Fix] Fixed encoding and user login value bug on links send by mail for some language/ plugin/ theme combinations
* [Fix] Fixed small spelling errors "profil" and inconsistencies in spelling "login", "registration", ...
* [Fix] Ensure admin panel preserves settings, when Professional version is disabled

= v2.0.0 (public release) =
* [Enhancement] Renaming from previously "th23 Frontent User Management"
* [Enhancement] Complete rebuild of plugin structure, including transfer into class
* [Enhancement] Leverage action and filter hooks to plugin professional parts, avoiding code duplication
* [Enhancement] Add option for user choosen passwords - including e-mail validation and re-validation after change of e-mail address
* [Enhancement] Add option for admin approval of new registrations - inlcuding option to send mail notification on new registrations requiring approval
* [Enhancement] Add backend functionality to manage "Pending" users (approve/ delete)
* [Enhancement] Validation and approval trackable via user meta data entries - for now only visible directly in DB
* [Enhancement] Add optional question / text field to be submitted upon registration (e.g. "Where did you find out about this website?", "I talked to your friend and she recommended it!") as basis for admin to approve / reject new user registrations
* [Enhancement] Reverse option to allow usage of wp-login.php - if allowed, users might work their way around additional options like mail validation, captcha or user approval by admin
* [Enhancement] Upgrade to latest reCaptcha version - API v2
* [Enhancement] Show more desciptive title for pages, e.g. "Login", "Your Profile", ... instead of "User Management"
* [Enhancement] Add "Logout of all other sessions" function on user management page
* [Fix] Fixed proper removal of page upon deactivation (required remove_action from post deletion hook to be successful)
* [Fix] Removed security through obscurity from "user_login" field upon registration to ensure password strength indicator works correctly
* [Fix] Prevent usage of "&" in user e-mail - as it causes issues upon storage, already in standard WordPress installation

= v1.6.0 =
* [Enhancement] Add nonce check to all forms - prevent automated attacks
* [Enhancement] Remove "dashboard" option for admin bar - since WP 3.3 adminbar on admin area is a must show
* [Enhancement] Add password strength indicator
* [Enhancement] reCAPTCHA implementation upon registration, lostpassword and (after x failed) login attempts
* [Enhancement] Change of email address via profile requires confirmation
* [Fix] Generate new activation key every time user is requesting a password reset

= v1.4.0 =
* [Enhancement] Simple Local Avatar plugin integration for professional version
* [Enhancement] th23 Subscribe plugin intrgration for professional version

= v1.2.0 (semi-public release) =
* [Enhancement] Introduce PLUGIN_SUPPORT_URL

= v1.0.0 (non-public release) =
* n/a

== Upgrade Notice ==

= v2.4.0 =
*** Professional users, please get an updated version of the "pro" file as well! ***

= v2.3.0 =
Everybody using translations of this plugin, please note that this release changes the handling of translations to comply with the newly introduced repository on translate.wordpress.org - this will require and updated version of the translation file(s)!
*** Professional users, please get an updated version of the "pro" file as well! ***

= v2.1.0 =
Please upgrade to this version to ensure compatibility with php5+ constructors and adapted WordPress coding standards!
*** Professional users, please get an updated version of the "pro" file as well! ***

= v2.0.1 =
Please upgrade to this version to ensure links sent via mail to users contain correct character encoding!
*** Professional users, please get an updated version of the "pro" file as well! ***

= v2.0.0 (public release) =
First public release - you should get this one ;-)
