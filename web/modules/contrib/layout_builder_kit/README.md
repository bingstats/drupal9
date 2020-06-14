Description
-----------
Layout Builder Kit includes basic components that you can use within
your Drupal site. The components can be used in Block Layout or
Layout Builder.

We have 7 components:
- Book Navigation
- Icon Text
- Image
- Render
- Rich Text
- Tab
- Video

Documentation
-------------
Find the documentation at:
https://performantlabs.com/layout-builder-kit/layout-builder-kit

Installation
------------
To install this module, you will require [Composer](https://getcomposer.org) on
your system.

On the command line, instruct Composer to add the module to your project:

```cd <project directory>```

```composer require drupal/layout_builder_kit```

Or, add the following to your project composer.json file with a text editor:

```"drupal/layout_builder_kit": "^1.0"```

Then run:
```composer update```

Composer will install the libraries and modules on which Layout Builder Kit depends.

Installing the module without Composer is not recommended and is unsupported.

Enabling the Module
-------------------
Enable the module by choosing it from /admin/modules or use Drush:

```cd <project directory>```

```drush en -y layout_builder_kit```

Allow Drupal to enable all the dependent modules.

To work with the Demo module, enable it in /admin/modules or use Drush:

```drush en -y layout_builder_kit_demo```
