CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Recommended modules
 * Installation

INTRODUCTION
------------
This is a module to demo a possible use of Layout BG. It generates a teaser
view mode of Article that displays the title and authored on date centered
over the Article image.

There is very minor templating and styling included in this example module.
All of the "heavy lifting" is being done by the Layout BG module. In fact, 
the styles that target the Article title could be eliminated by using a better
Field Formatter for the title that renders it as a h2 or something like that.

When disabling the module, all of the configuration is adds will be removed, 
so it won't have any lingering effects.

REQUIREMENTS
------------
This module creates a new view mode for the Article content type that is 
created during a Standard site install. If you didn't do a Standard site
install, this example might not install correctly.

Layout BG must be installed. And Views isn't technically required
but you should install it.

RECOMMENDED MODULES
-------------------
Views.

INSTALLATION
------------
Enable the module through the UI.

CONFIGURATION
-------------
To see the cool teaser effect, make sure Views is enabled 
and go to /layout_bg/article-teasers to see a grid of
Articles displayed as Layout BG Teasers.

To inspect the configuration of the Article view mode, go to
/admin/structure/types/manage/article/display/layout_bg_teaser/layout
