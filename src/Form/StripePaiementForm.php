<?php

namespace Drupal\stripebyhabeuk\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\stripebyhabeuk\Entity\ReliquatToPaid;
use Habeuk\Stripe\GateWay;
use Stripe\PaymentIntent;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripeAcompte;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class StripePaiement.
 */
class StripePaiementForm extends FormBase {
  
  /**
   * Drupal\commerce_price\MinorUnitsConverterInterface definition.
   *
   * @var \Drupal\commerce_price\MinorUnitsConverterInterface
   */
  protected $commercePriceMinorUnitsConverter;
  
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->commercePriceMinorUnitsConverter = $container->get('commerce_price.minor_units_converter');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stripe_paiement';
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $reliquat_to_paid = null) {
    $ReliquatToPaid = ReliquatToPaid::load($reliquat_to_paid);
    $form['#title'] = 'Payer le relicat de la commande : ' . $ReliquatToPaid->label();
    $form['paid'] = [];
    $this->payer($ReliquatToPaid, $form['paid']);
    // dump($form['paid']);
    $form['#tree'] = true;
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Payer maintenant'),
      '#button_type' => 'primary'
    ];
    //
    $form_state->set('reliquat_to_paid', $reliquat_to_paid);
    return $form;
  }
  
  /**
   * Permet d'effectuer les paiements reliquat_to_paid.
   * ( Mais il faudra que cela se fasse au niveau de l'entiter, ainsi on pourra
   * etendre cela facilement à d'autre entités ).
   */
  public function payer(ReliquatToPaid $ReliquatToPaid, &$build) {
    //
    $idHtml = Html::getUniqueId('cart-ifs-' . rand(100, 999));
    //
    $build['content'] = [
      '#type' => 'fieldset',
      '#title' => 'Information sur la commande ',
      '#collapsible' => TRUE, // Added
      '#collapsed' => FALSE // Added
    ];
    // information de base.
    $build['content'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'h6',
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
    //
    $build['paiement'] = [
      '#type' => 'fieldset',
      '#title' => 'Information sur la Carte bancaire (CB)',
      '#collapsible' => TRUE, // Added
      '#collapsed' => FALSE // Added
    ];
    // permet de sauvegarder l'id de PaymentIntent.
    $build['paiement']['stripebyhabeuk_payment_intent_id'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'payment-intent-id' . $idHtml
      ]
    ];
    // space to add iframe to collect cart information by StripeJs.
    $build['paiement']['cart_information'] = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      "#attributes" => [
        'id' => $idHtml,
        'class' => []
      ]
    ];
    
    if ($ReliquatToPaid->getAmountToPaid()) {
      //
      $url = Url::fromRouteMatch(\Drupal::routeMatch());
      $url->setAbsolute(true);
      $stripebyhabeuk = $this->getPaimentGateWay();
      $PaymentIntent = $this->createPaymentIntent($ReliquatToPaid, $stripebyhabeuk);
      // dump($PaymentIntent->id);
      // js.
      $build['#attached']['library'][] = 'stripebyhabeuk/stripejsinit';
      // pass and attach datas.
      $build['#attached']['drupalSettings']['stripebyhabeuk'] = [
        'publishableKey' => $stripebyhabeuk->getPublishableKey(),
        'idhtml' => $idHtml,
        'enable_credit_card_logos' => FALSE,
        'payment_status' => $PaymentIntent->status,
        'clientSecret' => $PaymentIntent->client_secret,
        'return_url' => $url->toString()
      ];
      
      $cacheability = new CacheableMetadata();
      // $cacheability->addCacheableDependency($this->entity);
      $cacheability->setCacheMaxAge(0);
      $cacheability->applyTo($build);
    }
    else {
      $this->messenger()->addStatus(" Aucun relicat pour cette commande ");
    }
    
    return $build;
  }
  
  /**
   *
   * @param string $id
   * @param string $plugin_id
   * @return array|\Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripeAcompte
   */
  protected function getPaimentGateWay($id = 'paiment_acompte', $plugin_id = "stripebyhabeuk_acompte") {
    /**
     * On initialise le chargeur de plugin de mail.
     *
     * @var \Drupal\commerce_payment\PaymentGatewayManager $PaymentGatewayManager
     */
    $PaymentGatewayManager = \Drupal::service('plugin.manager.commerce_payment_gateway');
    // le plugin doit etre configurer par un administrateur.
    $id = 'paiment_acompte';
    $plugin_id = "stripebyhabeuk_acompte";
    $configPlugin = $this->entityTypeManager->getStorage('commerce_payment_gateway')->load($id);
    if (!$configPlugin) {
      $this->messenger()->addError(" Paserrele de paiement non definit ");
      return [];
    }
    $confs = $configPlugin->toArray();
    if ($confs['plugin'] !== $plugin_id) {
      $this->messenger()->addError(" mauvaise passerelle de paiement ");
      return [];
    }
    /**
     *
     * @var \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripeAcompte $stripebyhabeuk
     */
    $stripebyhabeuk = $PaymentGatewayManager->createInstance($plugin_id, $confs['configuration']);
    return $stripebyhabeuk;
  }
  
  /**
   * Permet de creer ou de mettre à jour PaymentIntent.
   * S'assure egalement que pour une commande on a un unique paymentIntent.
   *
   * @param ReliquatToPaid $entity
   * @return \Stripe\PaymentIntent
   */
  public function createPaymentIntent(ReliquatToPaid $entity, StripeAcompte $stripebyhabeuk) {
    /** @var \Drupal\commerce_price\Price */
    $amount = $entity->getAmountToPaid();
    $intent_id = $entity->get('payment_intent_id')->value;
    // dump($intent_id);
    if ($amount)
      $intent_array = [
        'amount' => $this->commercePriceMinorUnitsConverter->toMinorUnits($amount),
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
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }
  
  /**
   * Verifie que le paiement a bien ete effectué et valide cela au niveau du
   * contenus.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $reliquat_to_paid = $form_state->get('reliquat_to_paid');
    $ReliquatToPaid = ReliquatToPaid::load($reliquat_to_paid);
    $stripebyhabeuk = $this->getPaimentGateWay();
    $PaymentIntent = $this->createPaymentIntent($ReliquatToPaid, $stripebyhabeuk);
    if ($PaymentIntent->status == PaymentIntent::STATUS_SUCCEEDED) {
      if ($ReliquatToPaid->getAmountToPaid()) {
        $ReliquatToPaid->set('amount_relicat_paid', $ReliquatToPaid->get('amount_to_paid')->first()->getValue());
      }
      $ReliquatToPaid->set('amount_to_paid', 0);
      $ReliquatToPaid->save();
      $form_state->setRedirect('entity.reliquat_to_paid.collection');
    }
  }
  
}
