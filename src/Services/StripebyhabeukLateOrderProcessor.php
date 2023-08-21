<?php

namespace Drupal\stripebyhabeuk\Services;

use Drupal\commerce_price\Price;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_payment\Entity\PaymentGateway;

/**
 * Completes the order refresh process for shipments.
 *
 * Saves any previously modified shipments.
 * Transfers the shipment amount and adjustments to the order.
 *
 * Runs after other order processors (promotion, tax, etc).
 *
 * @see \Drupal\commerce_shipping\EarlyOrderProcessor
 */
class StripebyhabeukLateOrderProcessor implements OrderProcessorInterface {
  
  use StringTranslationTrait;
  
  /**
   * Ce service est declenché pendant la MAJ par la sauvegarde d'une commande
   * pendant le checkout.
   *
   *
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    /**
     * payment_gateway se sont les types de paiments (CB, virements bancaire,
     * payer à la livraison) definis en administrations.
     */
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $order_payment_gateway */
    $order_payment_gateway = $order->get('payment_gateway')->entity;
    $PaymentOptionArray = $order->getData('stripebyhabeuk_select_payment_method');
    $db = [
      'order_payment_gateway' => $order_payment_gateway,
      'get_data' => $PaymentOptionArray
    ];
    if (!empty($PaymentOptionArray['payment_gateway_id'])) {
      /**
       *
       * @var \Drupal\commerce_payment\Entity\PaymentGateway $PaymentGateway
       */
      $PaymentGateway = PaymentGateway::load($PaymentOptionArray['payment_gateway_id']);
      $db['getBaseId'] = $PaymentGateway->getPlugin()->getBaseId();
      $db['getPluginId'] = $PaymentGateway->getPluginId();
      
      if ($PaymentGateway->getPlugin() instanceof \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripeAcompte) {
        /**
         *
         * @var \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripeAcompte $StripeAcompte
         */
        $StripeAcompte = $PaymentGateway->getPlugin();
        $db['StripeAcompte'] = $StripeAcompte;
        $db['totalPrice'] = $order->getTotalPrice();
        $db['totalPaid'] = $order->getTotalPaid();
        $db['sub-total'] = $order->getSubtotalPrice();
        if ($StripeAcompte->AcompteApplyOnSubtotal()) {
          $buildExplication = [
            '#type' => 'html_tag',
            '#tag' => 'small',
            '#attributes' => [
              'class' => [
                'font-italic'
              ]
            ],
            '#value' => 'Payer ' . $StripeAcompte->getSubToatlaAmount($order)->getNumber() . '' . $StripeAcompte->getCurrencySymbol($order) . ' sur ' . $order->getSubtotalPrice()->getNumber() . '' . $StripeAcompte->getCurrencySymbol($order)
          ];
        }
        else
          $buildExplication = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => ' Montant restant '
          ];
        $build = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => ' Montant restant '
          ],
          $buildExplication
        ];
        $output = \Drupal::service('renderer')->renderPlain($build);
        $db['output'] = $output;
        $order->addAdjustment(new Adjustment([
          'type' => 'accompte',
          'label' => $output,
          'amount' => $StripeAcompte->getBalanceToPay($order, false),
          'included' => FALSE,
          'locked' => FALSE,
          'source_id' => 'accompte_amount'
        ]));
      }
    }
  }
  
  /**
   * Whether shipments should be saved during processing.
   *
   * Normally this is true, but in certain circumstances, saving
   * should not occur. e.g. during shipment estimates.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *        The shipment.
   *        
   * @return bool Whether save should occur.
   */
  protected function shouldSave(ShipmentInterface $shipment): bool {
    return $shipment->hasTranslationChanges();
  }
  
}
