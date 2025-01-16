<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;

/**
 * Provides the interface for the Stripe payment gateway.
 */
interface StripebyHabeukInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {
  
  /**
   * Get the Stripe API Publisable key set for the payment gateway.
   *
   * @return string The Stripe API publishable key.
   */
  public function getPublishableKey();
  
  /**
   * Create a payment intent for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *        The order.
   * @param bool|array $intent_attributes
   *        (optional) Either an array of intent attributes or a boolean
   *        indicating
   *        whether the intent capture is automatic or manual. Passing a boolean
   *        is
   *        deprecated in 1.0-rc6. From 2.0 this parameter must be an array.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *        (optional) The payment.
   *        
   * @return \Stripe\PaymentIntent The payment intent.
   */
  public function createPaymentIntent(OrderInterface $order);
  
  /**
   * Retoune le montant qui doit etre payer.
   *
   * @param Price $montant
   *        // montant total de la commande.
   * @param OrderInterface $order
   *        // commande
   * @return integer // le montant de l'acompte.
   */
  public function amount(Price $amount, OrderInterface $order);
  
}
