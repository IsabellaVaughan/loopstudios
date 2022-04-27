<?php

namespace Drupal\image_replace\Tests;

use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of the replace image effect.
 */
abstract class ImageReplaceTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  protected function createImageField($name, $type_name, array $storage_settings = [], array $field_settings = [], array $widget_settings = []) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $type_name,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ]);
    $field_config->save();

    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('node', $type_name, 'default')
      ->setComponent($name, [
        'type' => 'image_image',
        'settings' => $widget_settings,
      ])
      ->save();

    $entity_display_repository->getViewDisplay('node', $type_name, 'default')
      ->setComponent($name)
      ->save();

    return $field_config;
  }

  /**
   * Create an image style containing the image repace effect.
   *
   * @param string $name
   *   The name of the new image style (all lowercase).
   *
   * @return array
   *   The newly created image style array.
   */
  protected function createImageStyle($name) {
    // Create an image style containing the replace effect.
    $style = ImageStyle::create([
      'name' => $name,
      'label' => $this->randomString(),
    ]);
    $effect = [
      'id' => 'image_replace',
      'data' => [],
    ];
    $style->addImageEffect($effect);
    $style->save();
    return $style;
  }

  /**
   * Create a pair of test files.
   *
   * @return array
   *   An array with two file objects (original_file, replacement_file).
   */
  protected function createTestFiles() {
    // Generate test images.
    $file_system = \Drupal::service('file_system');
    $original_uri = $file_system->copy(__DIR__ . '/fixtures/original.png', 'public://', FileSystemInterface::EXISTS_RENAME);
    $this->assertTrue($this->imageIsOriginal($original_uri));
    $this->assertFalse($this->imageIsReplacement($original_uri));
    $original_file = File::create([
      'filename' => $file_system->basename($original_uri),
      'uri' => $original_uri,
      'status' => FILE_STATUS_PERMANENT,
      'filemime' => \Drupal::service('file.mime_type.guesser')->guess($original_uri),
    ]);
    $original_file->save();

    $replacement_uri = $file_system->copy(__DIR__ . '/fixtures/replacement.png', 'public://', FileSystemInterface::EXISTS_RENAME);
    $this->assertTrue($this->imageIsReplacement($replacement_uri));
    $this->assertFalse($this->imageIsOriginal($replacement_uri));
    $replacement_file = File::create([
      'filename' => $file_system->basename($replacement_uri),
      'uri' => $replacement_uri,
      'status' => FILE_STATUS_PERMANENT,
      'filemime' => \Drupal::service('file.mime_type.guesser')->guess($replacement_uri),
    ]);
    $replacement_file->save();

    return [$original_file, $replacement_file];
  }

  /**
   * Returns TRUE if the image pointed at by the URI is the original image.
   */
  protected function imageIsOriginal($image_uri) {
    $expected_info = [
      'height' => 90,
      'mime_type' => 'image/png',
      'width' => 120,
    ];

    $image = \Drupal::service('image.factory')->get($image_uri);
    $image_info = [
      'height' => $image->getHeight(),
      'mime_type' => $image->getMimeType(),
      'width' => $image->getWidth(),
    ];

    // FIXME: Assert that original image has a red pixel on x=40, y=30.
    return $expected_info === $image_info;
  }

  /**
   * Returns TRUE if the image pointed at by the URI is the replacement image.
   */
  protected function imageIsReplacement($image_uri) {
    $expected_info = [
      'height' => 60,
      'mime_type' => 'image/png',
      'width' => 80,
    ];

    $image = \Drupal::service('image.factory')->get($image_uri);
    $image_info = [
      'height' => $image->getHeight(),
      'mime_type' => $image->getMimeType(),
      'width' => $image->getWidth(),
    ];

    // FIXME: Assert that replacement image has a green pixel on x=40, y=30.
    return $expected_info === $image_info;
  }

}
