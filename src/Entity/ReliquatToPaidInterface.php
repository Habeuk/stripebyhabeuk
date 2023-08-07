<?php

namespace Drupal\stripebyhabeuk\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Reliquat to paid entities.
 *
 * @ingroup stripebyhabeuk
 */
interface ReliquatToPaidInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Reliquat to paid name.
   *
   * @return string
   *   Name of the Reliquat to paid.
   */
  public function getName();

  /**
   * Sets the Reliquat to paid name.
   *
   * @param string $name
   *   The Reliquat to paid name.
   *
   * @return \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface
   *   The called Reliquat to paid entity.
   */
  public function setName($name);

  /**
   * Gets the Reliquat to paid creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Reliquat to paid.
   */
  public function getCreatedTime();

  /**
   * Sets the Reliquat to paid creation timestamp.
   *
   * @param int $timestamp
   *   The Reliquat to paid creation timestamp.
   *
   * @return \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface
   *   The called Reliquat to paid entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Reliquat to paid revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Reliquat to paid revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface
   *   The called Reliquat to paid entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Reliquat to paid revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Reliquat to paid revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface
   *   The called Reliquat to paid entity.
   */
  public function setRevisionUserId($uid);

}
