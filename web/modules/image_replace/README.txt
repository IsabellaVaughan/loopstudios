CONTENTS OF THIS FILE
=====================

 * Introduction
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
============

Image Replace provides a way to supply optional alternative source images
mapped to image styles. This is useful when building responsive sites with
"art directed" images where cropping and resizing is not enough but images
need to be swapped out completely. For example when delivering graphics
containing rendered text, e.g. responsive hero images.

This module works well with responsive image modules leveraging image styles,
for example the core "Responsive Image" module.

* For a full description of this module, visit the project page:
  https://www.drupal.org/project/image_replace

* To submit bug reports and feature suggestions, or track changes:
  https://www.drupal.org/project/issues/image_replace

* To learn more about Art direction as a use case for responsive images, see:
  https://usecases.responsiveimages.org/#art-direction


INSTALLATION
============

Install/Enable the image_replace module as you would normally
install a contributed Drupal module.

* Visit (https://www.drupal.org/node/1897420) for further information.

* Optionally you can also install using composer:
  composer require 'drupal/image_replace'


CONFIGURATION
=============

* See the contrib modules guide on drupal.org for detailed instructions:
  https://www.drupal.org/docs/contributed-modules/image-replace/

The following steps illustrate an example configuration working well together
with the Bartik theme:

* Navigate to Configuration -> Media -> Image Styles:
  * Add a new image style for large screens (e.g., Hero Image Wide). Add
    "Replace image" as the first effect.
  * Add a new image style for medium screens (e.g, Hero Image Narrow). Again,
    add "Replace image" as the first effect.
  * Add a new image style for small screens (e.g., Hero Image Small). Do not add
    the "Replace image" effect here.

* Navigate to Configuration -> Media -> Responsive image styles:
  * Add a new responsive image style (e.g., Hero Image). Select a breakpoint
    group (e.g., Bartik).
  * Set type to "select a single image style" for the wide breakpoint and choose
    "Hero Image Wide" as the image style.
  * Set type to "select a single image style" for the narrow breakpoint and
    choose "Hero Image Narrow" as the image style.
  * Set type to "select a single image style" for the mobile breakpoint and
    choose "Hero Image Mobile" as the image style.
  * Also set "Hero Image Mobile" as the fallback image style.

* Navigate to Structure -> Content types and select "Manage fields" on the
  "Article" type:
  * Add two new image fields "Image Wide" and "Image Narrow".
  * Edit the existing "Image" field and scroll down to the "Image Replace"
    settings:
    * Choose "Image Wide" as the source image field to use when rendered with
      the "Hero Image Wide" image style.
    * Choose "Image Narrow" as the source image field to use when rendered with
      the "Hero Image Narrow" image style.
  * Finally open the "Manage display" tab:
    * Choose "Responsive Image" formatter for the existing image field and
      select "Hero Image" as the responsive image style.
    * Hide the new image fields ("Image Wide", "Image Narrow") from all view
      modes.


MAINTAINERS
===========

Current maintainers:
* znerol (https://www.drupal.org/u/znerol)
