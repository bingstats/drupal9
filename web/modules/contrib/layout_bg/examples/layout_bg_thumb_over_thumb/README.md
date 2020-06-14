CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Recommended modules
 * Installation

INTRODUCTION
------------
There's no code in this example. It's 100% configuration. When disabling the
module, all of the configuration is adds will be removed, so it won't have
any lingering effects.

This is a module to demo a possible use of Layout BG. I made this module
in response to https://www.drupal.org/node/3067524 because it seemed like fun!

The idea is to show a scaled version of the original image in the foreground.
It is scaled such that the max width is 600px and the max height is 400px.
To maintain something of a consistent (and interesting) visual experience
across images of different aspect ratios, a black-and-white, rotated, scaled,
and cropped version of the image is shown in the background. This image will be
600px wide and 400px tall.

The layout is configured such that the foreground image will be centered over
the background image. The background image is actually an image tag displayed 
with object-fit such that it behaves much like a background iamge. However,
it takes up space in the DOM, so it essentially sets a responsive min-height
on the layout.

REQUIREMENTS
------------
Layout BG must be installed. This module requires core Media to be installed and
expects the core Image media type to exist. And Views isn't technically required
but you should install it.

RECOMMENDED MODULES
-------------------
Views.

INSTALLATION
------------
Enable the module through the UI.

CONFIGURATION
-------------
To see the cool image effect, make sure Views is enabled 
and go to /layout_bg/thumb-over-thumb to see a view page of
Image media displayed in the thumb-Over-Thumb view mode.

This module provides absolutely no extra styles. To look nice,
your theme should have img with max-width of 100% and height auto.
A lot of themes do that out of the box.

If there's no margin between the entities, it might look bad,
but you can add some margin in your theme or using dev tools.

To inspect the configuration of the media view mode, go to
/admin/structure/media/manage/image/display/thumb_over_thumb/layout

Inspect the Thumb OVer Thumb and Thumb Under Thumb image styles 
at /admin/config/media/image-styles
