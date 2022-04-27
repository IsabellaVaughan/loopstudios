<?php

namespace Drupal\image_replace\Tests;

use Drupal\image\Entity\ImageStyle;

/**
 * Tests functionality of the replace image effect.
 *
 * @group image_replace
 */
class ReplaceEffectTest extends ImageReplaceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image_replace'];

  /**
   * Tests functionality of the replace image effect.
   *
   * Functionality covered by this test include:
   * - image_replace_add()
   * - image_replace_get()
   * - image_replace_remove()
   * - image_replace_effect()
   */
  public function testReplaceEffect() {
    list($original_file, $replacement_file) = $this->createTestFiles();
    $storage_service = \Drupal::service('image_replace.storage');
    $file_system = \Drupal::service('file_system');

    // Create an image style containing the replace effect.
    $style_name = 'image_replace_test';
    $this->createImageStyle($style_name);

    // Apply the image style to a test image.
    $generated_url = ImageStyle::load($style_name)->buildUrl($original_file->getFileUri());
    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(200);

    $default_scheme = \Drupal::config('system.file')->get('default_scheme');
    $file_destination = $default_scheme . '://';
    // Assert that the result is the original image.
    $generated_uri = $file_system->saveData($generated_image_data, $file_destination);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file if there is no replacement mapping.');

    // Set up a replacement image.
    $storage_service->add($style_name, $original_file->getFileUri(), $replacement_file->getFileUri());
    ImageStyle::load($style_name)->flush();

    // Apply the image style to the test imge.
    $generated_url = ImageStyle::load($style_name)->buildUrl($original_file->getFileUri());
    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the result is the replacement image.
    $generated_uri = $file_system->saveData($generated_image_data, $file_destination);
    $this->assertTrue($this->imageIsReplacement($generated_uri), 'The generated file should be the same as the replacement file.');

    // Set up a replacement image.
    $storage_service->remove($style_name, $original_file->getFileUri(), $replacement_file->getFileUri());
    ImageStyle::load($style_name)->flush();

    // Apply the image style to a test image.
    $generated_url = ImageStyle::load($style_name)->buildUrl($original_file->getFileUri());
    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the result is the original image.
    $generated_uri = $file_system->saveData($generated_image_data, $file_destination);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file if the replacement mapping was removed.');
  }

}
