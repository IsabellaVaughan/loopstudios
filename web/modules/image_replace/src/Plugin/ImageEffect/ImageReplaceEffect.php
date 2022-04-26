<?php

namespace Drupal\image_replace\Plugin\ImageEffect;

use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;
use Drupal\image_replace\ImageReplaceStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rotates an image resource.
 *
 * @ImageEffect(
 *   id = "image_replace",
 *   label = @Translation("Replace image"),
 *   description = @Translation("Swap the original image if a replacement image was configured."),
 * )
 */
class ImageReplaceEffect extends ImageEffectBase {

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The image replace storage service.
   *
   * @var \Drupal\image_replace\ImageReplaceStorageInterface
   */
  protected $imageReplaceStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ImageFactory $image_factory, ImageReplaceStorageInterface $image_replace_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);

    $this->setConfiguration($configuration);
    $this->imageFactory = $image_factory;
    $this->imageReplaceStorage = $image_replace_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('image.factory'),
      $container->get('image_replace.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $configuration = $this->getConfiguration();
    $replacement_file = $this->imageReplaceStorage->get($configuration['data']['image_style'], $image->getSource());
    if ($replacement_file) {
      $toolkit_id = $image->getToolkitId();
      $replacement_image = $this->imageFactory->get($replacement_file, $toolkit_id);
      if ($replacement_image) {
        $image->apply('image_replace', ['replacement_image' => $replacement_image]);
      }
    }
  }

}
