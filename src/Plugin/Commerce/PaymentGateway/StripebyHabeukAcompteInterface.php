<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the interface for the Stripe payment gateway.
 */
interface StripebyHabeukAcompteInterface extends StripebyHabeukInterface {
  
  /**
   * Retoune le montant de l'acompte à payer.
   * L'acompte peut s'appliquer sur le montant total ou sur le sous total.
   * NB: Utiliser cette fonction si la valeur de la commande ne change par
   * durant le processus.
   *
   * @param OrderInterface $order
   * @return number //(unit amount)
   */
  public function acompte(OrderInterface $order);
  
  /**
   * Recupere le montant à payer
   *
   * @param OrderInterface $order
   * @return \Drupal\commerce_price\Price
   */
  public function getAcompteAmount(OrderInterface $order);
  
  /**
   * Recupere le montant restant à payer.
   *
   * @param OrderInterface $order
   * @return \Drupal\commerce_price\Price
   */
  public function getBalanceToPay(OrderInterface $order);
  
}
