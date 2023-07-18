<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class paymentIntentsStorage extends SqlContentEntityStorage implements paymentIntentsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(paymentIntentsInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {payment_intents_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {payment_intents_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
