<?php

namespace Drupal\stripebyhabeuk\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Payment intents entities.
 *
 * @ingroup stripebyhabeuk
 */
interface paymentIntentsInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Payment intents name.
   *
   * @return string
   *   Name of the Payment intents.
   */
  public function getName();

  /**
   * Sets the Payment intents name.
   *
   * @param string $name
   *   The Payment intents name.
   *
   * @return \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface
   *   The called Payment intents entity.
   */
  public function setName($name);

  /**
   * Gets the Payment intents creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Payment intents.
   */
  public function getCreatedTime();

  /**
   * Sets the Payment intents creation timestamp.
   *
   * @param int $timestamp
   *   The Payment intents creation timestamp.
   *
   * @return \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface
   *   The called Payment intents entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Payment intents revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Payment intents revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface
   *   The called Payment intents entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Payment intents revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Payment intents revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface
   *   The called Payment intents entity.
   */
  public function setRevisionUserId($uid);

}
