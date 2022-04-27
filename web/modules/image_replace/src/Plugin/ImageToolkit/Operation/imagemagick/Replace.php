<?php

namespace Drupal\image_replace\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\Image\ImageInterface;
use Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit;
use Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick\ImagemagickImageToolkitOperationBase;

/**
 * Defines Imagegemagick image_replace operation.
 *
 * @ImageToolkitOperation(
 *   id = "image_replace_imagemagick",
 *   toolkit = "imagemagick",
 *   operation = "image_replace",
 *   label = @Translation("Replace"),
 *   description = @Translation("Swap the original image with a replacement image."),
 * )
 */
class Replace extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return array(
      'replacement_image' => array(
        'description' => 'The replacement image',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    if (!($arguments['replacement_image'] instanceof ImageInterface || !($arguments['replacement_image']->getToolkit() instanceof ImagemagickToolkit))) {
      throw new \InvalidArgumentException("Invalid replacement image specified for the 'image_replace' operation.");
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = array()) {
    $replacement = $arguments['replacement_image']->getToolkit();

    // Replace original image's source with replacement image's source
    $this->getToolkit()->arguments()
      ->setSource($replacement->arguments()->getSource());
    $this->getToolkit()
      ->setWidth($replacement->getWidth())
      ->setHeight($replacement->getHeight());

    return TRUE;
  }
}
