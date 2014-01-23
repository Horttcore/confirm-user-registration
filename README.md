# Confirm User Registration

## Description

Admins have to confirm each user registration.
A notification will be send when the account gets approved.

## Installation

* Copy the confirm-user-registration.php into your plugin directory and activate the plugin.
* Go to ´Users > Confirm User Registration > Settings´ for further editing

## Usage

* Go to the Confirm User Registration menu in the user tab.
* Authenticate Users : Activate user accounts
* Block Users : Deactivate user accounts
* Option : Change some settings

## Frequently Asked Questions

### Will it create any new database tables?

No, it doesnt create any new tables. The Plugin uses user meta and options table

### Is there any language file?

Yes, its fully translateable.
English, german, italian and serbo-croatian are included.

## Screenshots

[![Screenshot of the auth/block users tab](https://raw.github.com/Horttcore/confirm-user-registration/master/screenshot-1.jpg)](https://raw.github.com/Horttcore/confirm-user-registration/master/screenshot-1.jpg)

[![Screenshot of the settings tab](https://raw.github.com/Horttcore/confirm-user-registration/master/screenshot-2.jpg)](https://raw.github.com/Horttcore/confirm-user-registration/master/screenshot-2.jpg)

[![Screenshot of login error](https://raw.github.com/Horttcore/confirm-user-registration/master/screenshot-3.jpg)](https://raw.github.com/Horttcore/confirm-user-registration/master/screenshot-3.jpg)

## Changelog

### v2.1.4

* Added Serbo-Croatian language file by Borisa Djuraskovic ( www.webhostinghub.com )

### v2.1.3

* Added action: `send_user_authentication`; Notification informations as parameters
* Added action: `confirm-user-registration-error`; Authentication error
* Enhancement: `confirm-user-registration-notification-header` filter has user as arguement
* Enhancement: `confirm-user-registration-notification-subject` filter has user as arguement
* Enhancement: `confirm-user-registration-notification-message` filter has user as arguement

### v2.1.2

* Translation: Italian language by ostroso
* Bugfix: Usernames with an `@` in it will be confirmed correctly

### v2.1.1

* Bugfix: Bulk delete checks for delete_users capability
* Bugfix: Missing translation for user roles

### v2.1

* Enhancement: Bulk authenticate, block and delete - props Chris Lee

### v2.0.4

* Enhancement: Added `edit` and `delete` links - props Chris Lee

### v2.0.3

* Fix: Removed whitespace - props Dainius

### v2.0.2

* Fix: You are able to block users again.

### v2.0.1

* Added action: `confirm-user-registration-auth-user`; User ID as parameter
* Added action: `confirm-user-registration-block-user`; User ID as parameter
* Added action: `confirm-user-registration-options`; for extending the plugin settings
* Added filter: `confirm-user-registration-error-message`; Display different error message
* Added filter: `confirm-user-registration-save-option`; Save custom options
* Added filter: `confirm-user-registration-notification-header`; Change notification e-mail header
* Added filter: `confirm-user-registration-notification-subject`; Change notification e-mail subject
* Added filter: `confirm-user-registration-notification-message`; Change notification e-mail message
* Fix: Translation error for de_DE
* Fix: No javascript loop when no user is selected
* Enhancement: Prevent to block your own account

### v2.0

* New design
* Completly rewritten

### v1.2.2

* Small Bugfix, Multilanguage Support

### v1.2.1

* Small Bugfix
* Changing authentication subject title

### v1.2

* Complete new interface so it looks like a normal WordPress Backend Site.
* Added authenticate accounts panel.
* Added block accounts panel.
* Added an options panel.
* User can edit the confirmation E-mail adress.
* User can edit the confirmation E-mail subject.
* User can edit the confirmation E-mail message.

### v1.1.1

* It works with WP 2.5

### v1.1

* First release
