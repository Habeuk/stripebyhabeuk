<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Payment intents entities.
 *
 * @ingroup stripebyhabeuk
 */
class paymentIntentsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Payment intents ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\stripebyhabeuk\Entity\paymentIntents $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.payment_intents.edit_form',
      ['payment_intents' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
