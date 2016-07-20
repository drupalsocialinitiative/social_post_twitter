<?php

namespace Drupal\social_post_twitter\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Twitter user entities.
 *
 * @ingroup social_post_twitter
 */
interface TwitterUserInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Twitter user entity name.
   *
   * @return string
   *   Name of the Twitter user entity.
   */
  public function getName();

  /**
   * Sets the Twitter user entity name.
   *
   * @param string $name
   *   The Twitter user entity name.
   *
   * @return \Drupal\social_post_twitter\Entity\TwitterUserInterface
   *   The called Twitter user entity entity.
   */
  public function setName($name);

  /**
   * Gets the Twitter user entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Twitter user entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Twitter user entity creation timestamp.
   *
   * @param int $timestamp
   *   The Twitter user entity creation timestamp.
   *
   * @return \Drupal\social_post_twitter\Entity\TwitterUserInterface
   *   The called Twitter user entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Twitter user entity published status indicator.
   *
   * Unpublished Twitter user entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Twitter user entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Twitter user entity.
   *
   * @param bool $published
   *   TRUE to set this Twitter user entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\social_post_twitter\Entity\TwitterUserInterface
   *   The called Twitter user entity entity.
   */
  public function setPublished($published);

}
