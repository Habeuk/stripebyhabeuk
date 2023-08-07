<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface;

/**
 * Defines the storage handler class for Reliquat to paid entities.
 *
 * This extends the base storage class, adding required special handling for
 * Reliquat to paid entities.
 *
 * @ingroup stripebyhabeuk
 */
class ReliquatToPaidStorage extends SqlContentEntityStorage implements ReliquatToPaidStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ReliquatToPaidInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {reliquat_to_paid_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {reliquat_to_paid_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ReliquatToPaidInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {reliquat_to_paid_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('reliquat_to_paid_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
