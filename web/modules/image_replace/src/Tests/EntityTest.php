<?php

namespace Drupal\image_replace\Tests;

use Drupal\Component\Utility\Html;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;

/**
 * Tests core entity API integration for the replace image effect.
 *
 * @group image_replace
 */
class EntityTest extends ImageReplaceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['views', 'node', 'image_replace'];

  /**
   * The test style name.
   *
   * @var string
   */
  protected $styleName;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an image style containing the replace effect.
    $this->styleName = 'image_replace_test';
    $this->createImageStyle($this->styleName);

    // Add the replacement image field to the article bundle.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->createImageField('image_replacement', 'article');

    // Add the original image field to the article bundle and specify
    // the replacement image as replacement.
    $field = $this->createImageField('image_original', 'article');
    $field->setThirdPartySetting('image_replace', 'image_style_map', [
      $this->styleName => [
        'source_field' => 'image_replacement',
      ],
    ]);
    $field->save();

    $entity_display_repository = \Drupal::service('entity_display.repository');
    $display = $entity_display_repository->getViewDisplay('node', 'article', 'teaser');
    $display_options = $display->getComponent('image_original');
    $display_options['type'] = 'image';
    $display_options['settings']['image_style'] = $this->styleName;
    $display->set('status', TRUE);
    $display->setComponent('image_original', $display_options);
    $display->save();

    $display = $entity_display_repository->getViewDisplay('node', 'article', 'full');
    $display_options = $display->getComponent('image_original');
    $display_options['type'] = 'image';
    $display_options['settings']['image_style'] = NULL;
    $display->set('status', TRUE);
    $display->setComponent('image_original', $display_options);
    $display->save();
  }

  /**
   * Tests image replacement on node entities.
   */
  public function testNodeView() {
    list($original_file, $replacement_file) = $this->createTestFiles();
    $file_system = \Drupal::service('file_system');

    $node = Node::create([
      'type' => 'article',
      'title' => $this->randomString(16),
      'promote' => 1,
    ]);

    $node->image_original->target_id = $original_file->id();
    $node->image_original->alt = $alt = $this->randomMachineName();
    $node->image_original->title = $title = $this->randomMachineName();
    $node->image_replacement->target_id = $replacement_file->id();
    $node->image_replacement->alt = $alt = $this->randomMachineName();
    $node->image_replacement->title = $title = $this->randomMachineName();
    $node->save();

    // Check teaser.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);
    $generated_url = ImageStyle::load($this->styleName)->buildUrl($node->image_original->entity->getFileUri());
    $relative_url = file_url_transform_relative($generated_url);
    $this->assertSession()->responseContains(Html::escape($relative_url));

    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(200);

    $default_scheme = \Drupal::config('system.file')->get('default_scheme');
    $file_destination = $default_scheme . '://';
    // Assert that the result is the replacement image.
    $generated_uri = $file_system->saveData($generated_image_data, $file_destination);
    $this->assertTrue($this->imageIsReplacement($generated_uri), 'The generated file should be the same as the replacement file on teaser.');

    // Check full view.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $generated_url = file_create_url($node->image_original->entity->getFileUri());
    $relative_url = file_url_transform_relative($generated_url);
    $this->assertSession()->responseContains(Html::escape($relative_url));

    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the result is the original image.
    $generated_uri = $file_system->saveData($generated_image_data, $file_destination);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file on full node view.');
  }

}
