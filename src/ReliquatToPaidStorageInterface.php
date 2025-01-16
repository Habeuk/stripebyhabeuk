<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ReliquatToPaidStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Reliquat to paid revision IDs for a specific Reliquat to paid.
   *
   * @param \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface $entity
   *   The Reliquat to paid entity.
   *
   * @return int[]
   *   Reliquat to paid revision IDs (in ascending order).
   */
  public function revisionIds(ReliquatToPaidInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Reliquat to paid author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Reliquat to paid revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface $entity
   *   The Reliquat to paid entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ReliquatToPaidInterface $entity);

  /**
   * Unsets the language for all Reliquat to paid with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
