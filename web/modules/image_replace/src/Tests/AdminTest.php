<?php

namespace Drupal\image_replace\Tests;

use Drupal\Component\Utility\Html;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;

/**
 * Tests the administrative interface of the image replace module.
 *
 * @group image_replace
 */
class AdminTest extends ImageReplaceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['views', 'node', 'field_ui', 'image_replace'];

  /**
   * The test style name.
   *
   * @var string
   */
  protected $styleName;

  /**
   * The test admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

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
    $this->createImageField('image_original', 'article');

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

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer image styles',
      'administer node fields',
      'administer nodes',
      'create article content',
      'delete any article content',
      'edit any article content',
    ]);
  }

  /**
   * Tests image replacement on node entities.
   */
  public function testFieldEditUi() {
    list($original_file, $replacement_file) = $this->createTestFiles();
    $file_system = \Drupal::service('file_system');

    // Create an unrelated image style.
    $unrelated_style_name = 'other_style';
    $style = ImageStyle::create([
      'name' => $unrelated_style_name,
      'label' => $this->randomString(),
    ]);
    $style->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.image_original');
    $this->assertSession()->statusCodeEquals(200);

    // Verify that a select field is present with a list of available source
    // fields for the generated image style.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $this->styleName . '][source_field]';
    $this->assertSession()->fieldExists($field_name);
    $items = $this->getSession()->getPage()->findField($field_name)->findAll('xpath', 'option');
    $contains_image_original = FALSE;
    $contains_image_replacement = FALSE;
    foreach ($items as $item) {
      $contains_image_original = $contains_image_original || $item->getValue() == 'image_original';
      $contains_image_replacement = $contains_image_replacement || $item->getValue() == 'image_replacement';
    }

    $this->assertFalse($contains_image_original, 'Original image field is not in the list of options.');
    $this->assertTrue($contains_image_replacement, 'Replacement image field is in the list of options.');

    // Verify that no select field is present for an image style which does not
    // contain the replacement effect.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $unrelated_style_name . '][source_field]';
    $this->assertSession()->fieldNotExists($field_name);

    // Choose the replacement image field as the replacement source.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $this->styleName . '][source_field]';
    $edit = [
      $field_name => 'image_replacement',
    ];
    $this->submitForm($edit, t('Save settings'));
    $this->assertSession()->statusCodeEquals(200);

    // Verify that no message is displayed if the mapping changes when there is
    // no existing content.
    $this->assertSession()->pageTextNotContains('The image replacement settings have been modified. As a result, it is necessary to rebuild the image replacement mapping for existing content. Note: The replacement mapping is updated automatically when saving an entity.');

    // Post new content.
    $edit = [
      'title[0][value]' => $this->randomString(),
      'promote[value]' => 1,
    ];
    $edit['files[image_original_0]'] = $file_system->realpath($original_file->getFileUri());
    $edit['files[image_replacement_0]'] = $file_system->realpath($replacement_file->getFileUri());
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'image_original[0][alt]' => $this->randomString(),
      'image_replacement[0][alt]' => $this->randomString(),
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $node = Node::load($matches[1]);

    // Verify that the original image is shown on the full node view.
    $generated_url = file_create_url($node->image_original->entity->getFileUri());
    $relative_url = file_url_transform_relative($generated_url);
    $this->assertSession()->responseContains(Html::escape($relative_url));

    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(200);

    $default_scheme = \Drupal::config('system.file')->get('default_scheme');
    $file_destination = $default_scheme . '://';
    // Assert that the result is the original image.
    $generated_uri = $file_system->saveData($generated_image_data, $file_destination);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file on full node view.');

    // Verify that the replacement image is shown on the teaser.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);
    $generated_url = ImageStyle::load($this->styleName)->buildUrl($node->image_original->entity->getFileUri());
    $relative_url = file_url_transform_relative($generated_url);
    $this->assertSession()->responseContains(Html::escape($relative_url));

    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the result is the replacement image.
    $generated_uri = $file_system->saveData($generated_image_data, $file_destination);
    $this->assertTrue($this->imageIsReplacement($generated_uri), 'The generated file should be the same as the replacement file on teaser.');

    // Go back to the field settings and reset the replacement mapping.
    $field_name = 'third_party_settings[image_replace][image_style_map][' . $this->styleName . '][source_field]';
    $edit = [
      $field_name => '0',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.image_original', $edit, t('Save settings'));
    $this->assertSession()->statusCodeEquals(200);

    // Verify that a message is displayed if the mapping changes when there is
    // existing content.
    $this->assertSession()->pageTextContains('The image replacement settings have been modified. As a result, it is necessary to rebuild the image replacement mapping for existing content. Note: The replacement mapping is updated automatically when saving an entity.');
  }

}
