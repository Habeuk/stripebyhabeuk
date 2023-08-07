<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Reliquat to paid entities.
 *
 * @ingroup stripebyhabeuk
 */
class ReliquatToPaidListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Reliquat to paid ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\stripebyhabeuk\Entity\ReliquatToPaid $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.reliquat_to_paid.edit_form',
      ['reliquat_to_paid' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
