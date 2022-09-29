CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Troubleshooting
* FAQ
* Maintainers


INTRODUCTION
------------
Custom Admin Menu allows to define a custom menu in a the admin toolbar.
It allows to add custom tree or hide default drupal admin menu that can be
considered as a technical tree but not so easy to use for final users.


REQUIREMENTS
------------

This module requires admin_toolbar.


RECOMMENDED MODULES
-------------------

- GinToolbar (https://www.drupal.org/project/gin):
  The gin theme toolbar tools.
- Gin (https://www.drupal.org/project/gin):
  The gin theme.


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

* Configure the user permissions in Administration » People » Permissions:

  - Use the administration pages and help (System module)

* Access administration form in Administration » Configuration » User
  Interface » Custom Admin Menu
  - Enable the custom admin menu
  - Insertion
    - When "Insert custom items in admin toolbar" is checked, the custom
      menu items are added to the default admin menu in the toolbar. If
      unselected, the menu is available at the root level of the toolbar.
    - Check "Wrap default admin menu in a single root menu item" to wrap
      the default menu items in a single root item. This will prioritize
      the use of the custom admin menu.
    - Shortcuts
    Shortcuts is used to add a specicic region of the admin theme.
      This region will be added in a the admin toolbar as an extension.

MAINTAINERS
-----------

Current maintainers:
* Thomas Sécher (tsecher) - https://www.drupal.org/u/tsecher


Supporting organization:
* Conserto - https://conserto.pro/
