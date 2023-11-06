<?php

namespace Drupal\stripebyhabeuk\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent;

/**
 *
 * @author stephane
 *        
 */
class ListPaiement implements EventSubscriberInterface {
  protected $paymentGateways = [];
  
  /**
   * Permet de retirer les paiements non authorisés.
   *
   * @param FilterPaymentGatewaysEvent $event
   */
  public function RemoveUnUserPaymentMethod(FilterPaymentGatewaysEvent $event) {
    $this->paymentGateways = $event->getPaymentGateways();
    $paymentGateways = [];
    foreach ($this->paymentGateways as $paymentGateway) {
    
    /**
     *
     * @var \Drupal\commerce_payment\Entity\PaymentGateway $paymentGateway
     */
      // if ($paymentGateway->isReusable()) {
      // $paymentGateways[] = $paymentGateway;
      // }
    }
    $event->setPaymentGateways($paymentGateways);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ // PaymentEvents::FILTER_PAYMENT_GATEWAYS => [
    // 'RemoveUnUserPaymentMethod'
    // ]
    ];
  }
  
}