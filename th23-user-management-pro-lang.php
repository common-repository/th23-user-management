<?php
/*
th23 User Management
Professional extension - Language strings

Copyright 2010-2019, Thorsten Hartmann (th23)
http://th23.net
*/

// This file should not be executed - but only be read by the gettext parser to prepare for translations
die();

// Function to extract i18n calls from PRO file
$file = file_get_contents('th23-user-management-pro.php');
preg_match_all("/__\\(.*?'\\)|\\/\\* translators:.*?\\*\\//s", $file, $matches);
foreach($matches[0] as $match) {
	echo $match . ";\n";
}

/* translators: parses in file name "wp-login.php" */;
__('%s is disabled', 'th23-user-management');
__('Failure Notice', 'th23-user-management');
__('Access to the admin area is restricted on this site!', 'th23-user-management');
/* translators: 1: homepage URL, 2: blog name */;
__('Please return to <a href="%1$s">%2$s</a>', 'th23-user-management');
__('Failure Notice', 'th23-user-management');
__('Sorry, you do not seem to be human - please solve the captcha shown to proof otherwise', 'th23-user-management');
/* translators: parses in "What? Why?" question into brackets and asociated tooltip, see following strings */;
__('Captcha - please follow the instructions below (%s)', 'th23-user-management');
__('What?', 'th23-user-management');
__('A captcha is a small test aiming to distinguish humans from computers.', 'th23-user-management');
__('Why?', 'th23-user-management');
__('Internet today needs to fight a lot of spam and this small test is required to keep this website clean.', 'th23-user-management');
__('What?', 'th23-user-management');
__('Why?', 'th23-user-management');
__('Enhanced security measures - please enter your password again and solve the shown captcha to login', 'th23-user-management');
__('You exceeded the maximum number of allowed login attempts - please enter your password again and solve the shown captcha to login', 'th23-user-management');
/* translators: shortcut for "not applicable" */;
__('n/a', 'th23-user-management');
__('Please define a password', 'th23-user-management');
__('Password may not contain the character "\"', 'th23-user-management');
__('The password confirmation does not match defined password, please try again', 'th23-user-management');
/* translators: mail title to new users pending address validation - blog name to be parsed in */;
__('[%s] Welcome / Validate your e-mail', 'th23-user-management');
/* translators: mail body to new users pending address validation - 1: blog name, 2: user login, 3: address validation link */;
__('Welcome to %1$s and thanks for joining!

Your username: %2$s

Your account is registered, but has to be activated by validating your e-mail address. Please confirm your e-mail address by visiting the following link:
%3$s', 'th23-user-management');
__('You have been registered, but the required verification mail could not be sent due to a server error. Please contact the administrator of this site', 'th23-user-management');
__('You have been registered successfully - please check your inbox for a mail containing a link for validating your e-mail address', 'th23-user-management');
/* translators: mail title to new users pending admin approval - blog name to be parsed in */;
__('[%s] Welcome / Your username and password', 'th23-user-management');
/* translators: mail body to new users pending admin approval - 1: blog name, 2: user login, 3: user password */;
__('Welcome to %1$s and thanks for joining!

Your username: %2$s
Your password: %3$s

Your registration is complete, but has to be approved by an administrator. You will receive another mail, once your registration has been approved and you can login using your username and password above.', 'th23-user-management');
__('You have been registered, but the required notification mail could not be sent due to a server error. Please contact the administrator of this site', 'th23-user-management');
__('You have been registered successfully, but need approval by an administrator - please check your inbox for initial password to login afterwards', 'th23-user-management');
/* translators: mail title to admin about new user pending approval - blog name to be parsed in */;
__('[%s] New user registration / Approval required', 'th23-user-management');
/* translators: optional part of mail body to admin about new user pending approval (see following string) - 1: registration question, 2: user answer */;
__('Upon the registration question "%1$s" the user answered:
%2$s

', 'th23-user-management');
/* translators: mail body to admin about new user pending approval - 1: blog name, 2: user login, 3: user mail, 4: question upon registration and user answer (see previous string), 5: admin user management page link */;
__('A user with the following details registered on your site %1$s and is pending your approval before being able to sign in:

Username: %2$s
E-mail: %3$s

%4$sPlease visit the user management page for your actions:
%5$s', 'th23-user-management');
__('Notification of an administrator requesting approval for your registration could not be sent due to a server error. Please contact the administrator of this site', 'th23-user-management');
/* translators: mail title to new user upon registration by admin - blog name to be parsed in */;
__('[%s] Welcome / Your username and password', 'th23-user-management');
/* translators: mail body to new user upon registration by admin - 1: blog name, 2: user login, 3: link to complete registration */;
__('Welcome to %1$s!

You have been registered by an administrator.

Your username: %2$s

To complete the registration and log in, please reset your password visiting the following link:
%3$s', 'th23-user-management');
__('A confirmation will be sent to the given e-mail address. Please click the link provided in there to validate your e-mail address.', 'th23-user-management');
__('New members require approval by an admin. You will only be able to login, once your registration has been approved.', 'th23-user-management');
__('Password', 'th23-user-management');
__('Confirm password', 'th23-user-management');
__('Password strength indicator', 'th23-user-management');
/* translators: shortcut for "not applicable" */;
__('n/a', 'th23-user-management');
__('Validation of your e-mail address failed, the given user is invalid - please try again or contact the site administrator', 'th23-user-management');
__('Your e-mail address has been validated', 'th23-user-management');
__('Your e-mail address is already verified', 'th23-user-management');
__('Validation of your e-mail address failed, the given validation key is invalid - please try again or contact the site administrator', 'th23-user-management');
__('Your registration still requires approval by an admin - you will get a notification mail once your account is approved and you can log in', 'th23-user-management');
/* translators: mail title to admin for new user pending admin approval after mail validation - blog name to be parsed in */;
__('[%s] New user registration / Approval required', 'th23-user-management');
/* translators: optional part of mail body to admin for new user pending admin approval after mail validation (see following string) - 1: registration question, 2: user answer */;
__('Upon the registration question "%s" the user answered:
%2$s

', 'th23-user-management');
/* translators: mail body to admin for new user pending admin approval after mail validation - 1: blog name, 2: user login, 3: user mail, 4: question upon registration and user answer (see previous string), 5: admin user management page link */;
__('A user with the following details registered on your site %1$s, validated his e-mail address and is pending your approval before being able to sign in:

Username: %2$s
E-mail: %3$s

%4$sPlease visit the user management page for your actions:
%5$s', 'th23-user-management');
__('Notification to an administrator requesting approval for your registration could not be sent due to a server error. Please contact the administrator of this site', 'th23-user-management');
__('Your registration is pending validation of your e-mail address - please click the link in the mail sent to you upon your registration or contact the site administrator', 'th23-user-management');
__('Your registration is pending approval by a site administrator - please wait to be notified by mail or contact the site administrator', 'th23-user-management');
__('Your registration is pending - please contact the site administrator', 'th23-user-management');
__('Attention', 'th23-user-management');
__('There are new users pending approval not shown in the list below - please view tab "<a href="%s">Pending</a>" to review these users', 'th23-user-management');
/* translators: mail title to new user after admin approval - blog name to be parsed in */;
__('[%s] Welcome / Ready for login', 'th23-user-management');
/* translators: option 1 for part of mail body to new user after admin approval (see following strings) */;
__('Your password: As chosen by you upon registration

In case you can not remember your password, use the following link to reset it:', 'th23-user-management');
/* translators: option 2 for part of mail body to new user after admin approval (see following string) */;
__('Your password: As sent in a previous mail

In case you do not have the mail with your password anymore, use the following link to reset it:', 'th23-user-management');
/* translators: mail body to new user after admin approval - 1: blog name, 2: link to login page, 3: user login, 4: password hint (see previous strings), 5: link to lost password page */;
__('Welcome once again to %1$s - your registration has been approved and you can now login using the following link:
%2$s

Your username: %3$s
%4$s
%5$s', 'th23-user-management');
__('Error', 'th23-user-management');
__('An error occured, while sending notification to user with the ID: %s', 'th23-user-management');
__('Done', 'th23-user-management');
__('Selected user(s) have been approved', 'th23-user-management');
__('Role - Reason', 'th23-user-management');
__('Answer to registration question', 'th23-user-management');
/* translators: user status for usage in column of a table */;
__('Pending - Mail validation', 'th23-user-management');
__('Pending - Admin approval', 'th23-user-management');
__('Delete', 'th23-user-management');
__('Approve', 'th23-user-management');
/* translators: shortcut for "not applicable" */;
__('n/a', 'th23-user-management');
__('Action cancelled, no changes have been saved', 'th23-user-management');
__('Invalid request - please use the form below to update your profile', 'th23-user-management');
__('Your profile has been updated', 'th23-user-management');
/* translators: mail title to user after password change - blog name to be parsed in */;
__('[%s] Notice of changed password', 'th23-user-management');
/* translators: mail body to user after password change - 1: blog name, 2: admin mail address */;
__('The password for your account on %1$s has been changed.

If you did not change it, please contact the Site Administrator:
%2$s', 'th23-user-management');
/* translators: mail title to user for validation of mail address change - blog name to be parsed in */;
__('[%s] Validate changed e-mail address', 'th23-user-management');
/* translators: mail body to user for validation of mail address change (option 1: after own change) - 1: blog name, 2: link for mail validation */;
__('You changed the e-mail address for your account on %1$s.

Please confirm your new e-mail address by visiting the following link:
%2$s', 'th23-user-management');
/* translators: mail body to user for validation of mail address change (option 2: admin initiated change) - 1: blog name, 2: link for mail validation */;
__('The e-mail address for your account on %1$s has been changed.

Please confirm your new e-mail address by visiting the following link:
%2$s', 'th23-user-management');
__('The required verification mail for changing the e-mail address could not be sent due to a server error. The new address has not been saved, please try again or contact the administrator of this site', 'th23-user-management');
__('The change of your e-mail address requires confirmation - please check your new inbox and follow the validation link provided', 'th23-user-management');
__('Validation of your e-mail address failed, the given user is invalid - please try again or contact the site administrator', 'th23-user-management');
__('No pending change of e-mail could be found - please try again the link, try again to change your e-mail address or contact the site administrator', 'th23-user-management');
__('Update of your e-mail address failed - please try again or contact the site administrator', 'th23-user-management');
__('Your e-mail address has successfully been changed', 'th23-user-management');
__('Validation of your e-mail address failed, the given validation key is invalid - please try again the link, try again to change your e-mail address or contact the site administrator', 'th23-user-management');
/* translators: mail title to user after change of mail address - blog name to be parsed in */;
__('[%s] Notice of changed e-mail address', 'th23-user-management');
/* translators: mail body to user after change of mail address - do NOT change "###SITENAME###", "###USERNAME###" or "###ADMIN_EMAIL###" strings */;
__('The e-mail address for your account (###USERNAME###) on ###SITENAME### has been changed.

If you did not change it, please contact the Site Administrator:
###ADMIN_EMAIL###', 'th23-user-management');
__('Attention', 'th23-user-management');
/* translators: parses in new mail address */;
__('Pending change of your e-mail address requires confirmation - please check your new inbox "%s" and follow the validation link provided', 'th23-user-management');
/* translators: parses in new mail address */;
__('There is a pending change of e-mail address for this user - intended new address is "%s"', 'th23-user-management');
/* translators: mail title to user for password reset triggered by admin - blog name to be parsed in */;
__('[%s] Reset password', 'th23-user-management');
/* translators: mail body to user for password reset triggered by admin - 1: blog name, 2: password reset link */;
__('An administrator initiated a password reset for your account on %1$s.

Please reset your password visiting the following address:
%2$s', 'th23-user-management');
/* translators: shortcut for "not applicable" */;
__('n/a', 'th23-user-management');
__('Invalid request - please use the form below to register', 'th23-user-management');
__('Enter a username or e-mail address', 'th23-user-management');
__('There is no user registered with that e-mail', 'th23-user-management');
__('There is no user registered with that username', 'th23-user-management');
__('Password reset is not possible for this user', 'th23-user-management');
/* translators: mail title to user for initiating password reset - blog name to be parsed in */;
__('[%s] Reset password', 'th23-user-management');
/* translators: mail body to user for initiating password reset - 1: blog name, 2: password reset link */;
__('Someone requested the password to be reset for your account on %1$s.

If this is a mistake, just ignore this email and nothing will happen.

To reset your password, please visit the following address:
%2$s', 'th23-user-management');
__('The e-mail to reset your password could not be sent due to a server error. Please try again - if the error persists contact the administrator of this site', 'th23-user-management');
__('The reset of your password has been initiated successfully - please check your inbox and follow the link provided', 'th23-user-management');
__('Resetting your password failed, the given user is invalid - please try again or contact the site administrator', 'th23-user-management');
__('Resetting your password failed, the verification key is invalid - please try again or contact the site administrator', 'th23-user-management');
__('Invalid request - please use the form below to reset your password', 'th23-user-management');
/* translators: mail title to user after password change - blog name to be parsed in */;
__('[%s] Notice of changed password', 'th23-user-management');
/* translators: mail body to user after password change - 1: blog name, 2: admin mail address */;
__('The password for your account on %1$s has been changed.

If you did not change it, please contact the Site Administrator:
%2$s', 'th23-user-management');
__('Your password has been changed - please login using your new password', 'th23-user-management');
__('Lost Password', 'th23-user-management');
__('Please fill out the form below. You can enter your username or email address. You will receive a link to create a new password via email.', 'th23-user-management');
__('Username or e-mail', 'th23-user-management');
__('Get New Password', 'th23-user-management');
__('Log in', 'th23-user-management');
__('Register', 'th23-user-management');
__('Trouble upon password reset?', 'th23-user-management');
__('Reset Password', 'th23-user-management');
__('Please enter a new password for your account.', 'th23-user-management');
__('New password', 'th23-user-management');
__('Confirm new password', 'th23-user-management');
__('Password strength indicator', 'th23-user-management');
/* translators: shortcut for "not applicable" */;
__('n/a', 'th23-user-management');
__('Save Password', 'th23-user-management');
__('Log in', 'th23-user-management');
__('Register', 'th23-user-management');
__('Trouble upon password reset?', 'th23-user-management');

?>
