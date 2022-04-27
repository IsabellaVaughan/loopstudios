<?php

namespace Drupal\image_replace;

use Drupal\Core\Database\Connection;

/**
 * Defines a class for image_replace database storage operations.
 */
class ImageReplaceDatabaseStorage implements ImageReplaceStorageInterface {

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ImageReplaceDatabaseStorage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function get($target_style, $target_uri) {
    $target_uri_hash = hash('sha256', $target_uri);
    return $this->database->select('image_replace')
      ->fields('image_replace', ['replacement_uri'])
      ->condition('target_style', $target_style)
      ->condition('target_uri_hash', $target_uri_hash)
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function add($target_style, $target_uri, $replacement_uri) {
    $target_uri_hash = hash('sha256', $target_uri);
    return $this->database->insert('image_replace')
      ->fields([
        'target_style' => $target_style,
        'target_uri_hash' => $target_uri_hash,
        'replacement_uri' => $replacement_uri,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function remove($target_style, $target_uri) {
    $target_uri_hash = hash('sha256', $target_uri);
    return $this->database->delete('image_replace')
      ->condition('target_style', $target_style)
      ->condition('target_uri_hash', $target_uri_hash)
      ->execute();
  }

}
