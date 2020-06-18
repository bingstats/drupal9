CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------
 Full documentation including examples with screenshots and code
 snippets can be found at: https://www.drupal.org/docs/8/modules/layout-bg
 
 Provides Layout plugins that feature a background region. The background
 region can accept any number of blocks. The first block that is not empty
 will be rendered and the magic of object-fit is used to make the image
 (assuming the block contains an image!) behave like a background image.
 The background also works ok with video tags as long as they autoplay. 
 
 Since it's not really a background image and is instead an actual img
 tag, there's a ridiculous amount of flexibility! Most likely, you will
 be adding a field block to the background region, where the field is an
 image field or a media reference field. You can use any field formatter
 that renders the field as an img tag. It works with just about anything!
   1. Image and Media Thumbnails
   2. Image styles
   3. Responsive Images
   4. Blazy (lazy loading)
   5. Images in a WYSIWYG
   6. Some autoplay slideshows
   7. Autoplay videos

 And none of it takes any more configuration than you are used to! It's
 all thanks to the magic of object-fit.

 Just to clarify, you do not need to (nor should you) use any special field 
 formatters that are typically used for background images. This module gives
 the illusion of background images while still using real img tags. That's
 why it's so easily compatible with Blazy and Responsive Images.
 
 * For a full description of the module, visit the project page:
   https://drupal.org/project/layout_bg

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/layout_bg
   
 Full documentation including examples with screenshots and code
 snippets can be found at: https://www.drupal.org/docs/8/modules/layout-bg

REQUIREMENTS
------------

 * Layout Builder and Layout Discovery (both in core) are required.

RECOMMENDED MODULES
-------------------

 * This module works great with Responsive Image and lazy loading modules
   like Blazy.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

 * There is no global configuration for this module.
 * Any Layout Section provided by this module has several configuration options.
   Specifically, you may configure a background color, overlay color, 
   overlay opacity, and text color. There is also a configuration option that 
   controls the behavior of the background image.

TROUBLESHOOTING
---------------

 * Q: Does your browser support object-fit?
   A: If not, this won't work.
 
 * Q: Is there more than a single img tag being rendered in the background?
   A: If so, that may mess things up. Avoid text in the background region.
      Block labels get suppressed by default for this reason.


MAINTAINERS
-----------

Current maintainers:

 * danflanagan8
