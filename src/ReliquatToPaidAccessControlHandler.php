<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Reliquat to paid entity.
 *
 * @see \Drupal\stripebyhabeuk\Entity\ReliquatToPaid.
 */
class ReliquatToPaidAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished reliquat to paid entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published reliquat to paid entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit reliquat to paid entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete reliquat to paid entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add reliquat to paid entities');
  }


}
