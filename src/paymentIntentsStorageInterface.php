<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\stripebyhabeuk\Entity\paymentIntentsInterface;

/**
 * Defines the storage handler class for Payment intents entities.
 *
 * This extends the base storage class, adding required special handling for
 * Payment intents entities.
 *
 * @ingroup stripebyhabeuk
 */
interface paymentIntentsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Payment intents revision IDs for a specific Payment intents.
   *
   * @param \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface $entity
   *   The Payment intents entity.
   *
   * @return int[]
   *   Payment intents revision IDs (in ascending order).
   */
  public function revisionIds(paymentIntentsInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Payment intents author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Payment intents revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
