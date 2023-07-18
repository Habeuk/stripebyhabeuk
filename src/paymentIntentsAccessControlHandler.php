<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Payment intents entity.
 *
 * @see \Drupal\stripebyhabeuk\Entity\paymentIntents.
 */
class paymentIntentsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished payment intents entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published payment intents entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit payment intents entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete payment intents entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add payment intents entities');
  }


}
