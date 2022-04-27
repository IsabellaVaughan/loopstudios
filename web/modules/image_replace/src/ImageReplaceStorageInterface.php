<?php

namespace Drupal\image_replace;

/**
 * Defines the interface for image_replace storage implementations.
 */
interface ImageReplaceStorageInterface {

  /**
   * Determine replacement image uri for the given original filename.
   *
   * @param string $target_style
   *   The target image style name.
   * @param string $target_uri
   *   The uri of the image for which to find a replacement.
   *
   * @return string|null
   *   The replacement uri when a mapping for the given uri/style combination
   *   exists.
   */
  public function get($target_style, $target_uri);

  /**
   * Add an image replacement mapping.
   *
   * @param string $target_style
   *   The target image style name.
   * @param string $target_uri
   *   The uri of the image for which to set a replacement.
   * @param string $replacement_uri
   *   The replacement uri to set for the given uri/style combination.
   */
  public function add($target_style, $target_uri, $replacement_uri);

  /**
   * Remove the given image replacement mapping if it exists.
   *
   * @param string $target_style
   *   The target image style name.
   * @param string $target_uri
   *   The uri of the image for which to remove the replacement.
   */
  public function remove($target_style, $target_uri);

}
