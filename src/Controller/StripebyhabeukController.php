<?php

namespace Drupal\stripebyhabeuk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\stripebyhabeuk\Entity\ReliquatToPaid;
use Habeuk\Stripe\GateWay;
use Stripe\PaymentIntent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_price\MinorUnitsConverterInterface;

/**
 * Returns responses for stripe by habeuk routes.
 */
class StripebyhabeukController extends ControllerBase {
  
  /**
   * The minor units converter.
   *
   * @var \Drupal\commerce_price\MinorUnitsConverterInterface
   */
  protected $minorUnitsConverter;
  
  function __construct(MinorUnitsConverterInterface $minor_units_converter) {
    $this->minorUnitsConverter = $minor_units_converter;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('commerce_price.minor_units_converter'));
  }
  
  /**
   *
   * @var string
   */
  protected $endpointSecret = '';
  
  /**
   * Builds the response.
   */
  public function build() {
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!')
    ];
    return $build;
  }
  
  /**
   * Permet d'effectuer les paiements reliquat_to_paid.
   * ( Mais il faudra que cela se fasse au niveau de l'entiter, ainsi on pourra
   * etendre cela facilement à d'autre entités ).
   */
  public function payer($reliquat_to_paid) {
    $ReliquatToPaid = ReliquatToPaid::load($reliquat_to_paid);
    $build['#title'] = 'Payer le relicat de la commande : ' . $ReliquatToPaid->label();
    if ($ReliquatToPaid->getAmountToPaid()) {
      // information de base.
      $build['content'] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'h4',
          '#value' => 'Montant restant à payer : ',
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $ReliquatToPaid->getAmountToPaid()
          ]
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $ReliquatToPaid->getOrderInformation()
        ]
      ];
      $paymentIntent = $this->createPaymentIntent($ReliquatToPaid);
    }
    else {
      $this->messenger()->addStatus(" Aucun relicat pour cette commande ");
    }
    
    return $build;
  }
  
  /**
   * Permet de creer ou de mettre à jour PaymentIntent.
   * S'assure egalement que pour une commande on a un unique paymentIntent.
   *
   * @param ReliquatToPaid $entity
   * @return \Stripe\PaymentIntent
   */
  public function createPaymentIntent(ReliquatToPaid $entity) {
    /** @var \Drupal\commerce_price\Price */
    $amount = $entity->getAmountToPaid();
    $intent_id = $entity->get('payment_intent_id')->value;
    $intent_array = [
      'amount' => $this->minorUnitsConverter->toMinorUnits($amount),
      'currency' => strtolower($amount->getCurrencyCode()),
      'payment_method_types' => [
        'card'
      ],
      'metadata' => [
        'reliquat_to_paid_id' => $entity->id(),
        'order_id' => $entity->get('commerce_order')->target_id
      ],
      // 'payment_method' => $payment_method->getRemoteId(),
      'capture_method' => 'automatic'
    ];
    /**
     * On initialise le chargeur de plugin de mail.
     *
     * @var \Drupal\commerce_payment\PaymentGatewayManager $PaymentGatewayManager
     */
    $PaymentGatewayManager = \Drupal::service('plugin.manager.commerce_payment_gateway');
    /**
     *
     * @var \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripeAcompte $stripebyhabeuk
     */
    $stripebyhabeuk = $PaymentGatewayManager->createInstance('stripebyhabeuk_acompte');
    
    $order = \Drupal\commerce_order\Entity\Order::load($entity->get('commerce_order')->target_id);
    if ($order) {
      // $customer_remote_id =
      // $this->getRemoteCustomerId($order->getCustomer());
      // if (!empty($customer_remote_id)) {
      // $intent_array['customer'] = $customer_remote_id;
      // }
    }
    
    $GateWay = new GateWay($stripebyhabeuk->getSecretKey());
    /**
     *
     * @var \Stripe\StripeClient $stribeLib
     */
    $stribeLib = $GateWay->getInstance();
    if (!$intent_id) {
      // le paiement se ferra lorsque l'utilisateur va cliquer sur le bouton
      // "paiyer la facture".
      $intent_array['confirm'] = false;
      $paymentIntents = $stribeLib->paymentIntents->create($intent_array);
      // cette sauvegarde de order, contient des informations prevenant de
      // amount().
      $entity->set('payment_intent_id', $paymentIntents->id);
    }
    else {
      /**
       * On verifie si l'utilisateur a deja payé ou pas.
       */
      $paymentIntents = $stribeLib->paymentIntents->retrieve($intent_id);
      if ($paymentIntents->status == PaymentIntent::STATUS_SUCCEEDED) {
        return $paymentIntents;
      }
      // On maj les données.
      $paymentIntents = $stribeLib->paymentIntents->update($intent_id, $intent_array);
    }
    $entity->save();
    return $paymentIntents;
  }
  
  /**
   * --
   */
  public function webhookCharge() {
    $this->endpointSecret = "";
    $configs = [
      'hello'
    ];
    return $this->reponse($configs, 200);
  }
  
  /**
   *
   * @param array|string $configs
   * @param number $code
   * @param string $message
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  protected function reponse($configs, $code = null, $message = null) {
    if (!is_string($configs))
      $configs = Json::encode($configs);
    $reponse = new JsonResponse();
    if ($code)
      $reponse->setStatusCode($code, $message);
    $reponse->setContent($configs);
    return $reponse;
  }
  
}
