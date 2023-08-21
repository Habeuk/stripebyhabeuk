<?php

namespace Drupal\stripebyhabeuk;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\stripebyhabeuk\Entity\ReliquatToPaid;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Reliquat to paid entities.
 *
 * @ingroup stripebyhabeuk
 */
class ReliquatToPaidListBuilder extends EntityListBuilder {
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = '#ID';
    $header['name'] = $this->t('Cart');
    $header['user_id'] = "Client";
    $header['amount_to_paid'] = "Montant restant à payer";
    $header['commerce_order'] = "Information sur la commande";
    return $header + parent::buildHeader();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\stripebyhabeuk\Entity\ReliquatToPaid $entity */
    $amountToPaid = $entity->getAmountToPaid();
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    $row['user_id'] = $entity->getOwner()->label();
    $row['amount_to_paid'] = $amountToPaid ? $amountToPaid : '0 €';
    $row['commerce_order'] = $entity->getOrderInformation();
    return $row + parent::buildRow($entity);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = $this->CustomOperations($entity);
    // $operations += $this->getDefaultOperations($entity);
    // $operations += $this->moduleHandler()->invokeAll('entity_operation', [
    // $entity
    // ]);
    // $this->moduleHandler->alter('entity_operation', $operations, $entity);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    
    return $operations;
  }
  
  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *        The entity the operations are for.
   *        
   * @return array The array structure is identical to the return value of
   *         self::getOperations().
   */
  protected function CustomOperations(ReliquatToPaid $entity) {
    $operations = [];
    if ($entity->access('update') && $entity->getAmountToPaid()) {
      $operations['paid'] = [
        'title' => 'Payer',
        'weight' => 10,
        'url' => Url::fromRoute('stripebyhabeuk.stripe_paiement', [
          'reliquat_to_paid' => $entity->id()
        ], [
          'query' => $this->getRedirectDestination()->getAsArray()
        ])
      ];
    }
    
    return $operations;
  }
  
}
