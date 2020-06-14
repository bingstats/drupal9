Layout Builder BGColor
----------------------
This module allows setting the background color of a layout in Layout Builder.
In theory it should work with any layout system, but it has only been tested
with Layout Builder.

The available list of colors is managed at a central settings page:
* /admin/config/user-interface/layout-builder-bgcolor

These will then be listed on the settings form for each layout.


Requirements
--------------------------------------------------------------------------------
Layout Builder from core is required, no contributed modules are needed.


Features
--------------------------------------------------------------------------------
The primary features include:

* A central settings page to control which colors are available.

* Support for core's included layouts.

* Support for additional layouts by setting the its "class" attribute to:
  \Drupal\layout_builder_bgcolor\Plugin\Layout\LayoutBase


Known issues
--------------------------------------------------------------------------------
* This is not currenctly compatible with Bootstrap Layouts.


Credits / contact
--------------------------------------------------------------------------------
Written by Joshua Boltz [1], comaintained by Damien McKenna [2].

Ongoing development is sponsored by Mediacurrent [3].

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/layout_builder_bgcolor


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/u/joshuaboltz
2: https://www.drupal.org/u/damienmckenna
3: https://www.mediacurrent.com
