<?php

namespace Drupal\stripebyhabeuk\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides payment form for Stripe.
 */
class StripebyhabeukStaticOnSiteCheckoutForm extends PaymentMethodAddForm implements TrustedCallbackInterface {
  
  /**
   * Builds the credit card form.
   *
   * @param array $element
   *        The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *        The current state of the complete form.
   *        
   * @return array The built credit card form.
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // Set our key to settings array.
    /** @var \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripebyhabeukStaticOnSite $plugin */
    $plugin = $this->plugin;
    
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $idHtml = Html::getUniqueId('cart-ifs-' . rand(100, 999));
    $idHtml = !empty($element['#id']) ? $element['#id'] : Html::getUniqueId('cart-ifs-' . rand(100, 999));
    // space to add iframe to collect cart information by StripeJs.
    $element['titre_cart'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      "#attributes" => [],
      '#value' => 'Information sur la carte'
    ];
    // ici on cree des champs vide envue de passer la validation de la CB.
    
    // permet de sauvegarder l'id de PaymentMethod.
    $element['stripebyhabeuk_payment_method_id'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'payment-method-id' . $idHtml
      ]
    ];
    // space to add iframe to collect cart information by StripeJs.
    $element['cart_information'] = [
      '#type' => 'html_tag',
      '#tag' => 'section',
      "#attributes" => [
        'id' => $idHtml,
        'class' => []
      ]
    ];
    
    // js.
    $element['#attached']['library'][] = 'stripebyhabeuk/stripejsinit';
    // pass and attach datas.
    $element['#attached']['drupalSettings']['stripebyhabeuk'] = [
      'publishableKey' => $plugin->getPublishableKey(),
      'idhtml' => $idHtml,
      'enable_credit_card_logos' => FALSE
    ];
    
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->entity);
    $cacheability->setCacheMaxAge(0);
    $cacheability->applyTo($element);
    return $element;
  }
  
  protected function getPaymentIndent($order_id) {
    $datas = $this->entityTypeManager->getStorage('payment_intents')->loadByProperties([
      'commerce_order' => $order_id
    ]);
    if ($datas) {
      /**
       *
       * @var \Drupal\stripebyhabeuk\Entity\paymentIntents $payment_intents
       */
      $payment_intents = reset($datas);
      return $payment_intents->getStripePaymentIntentsId();
    }
    return null;
  }
  
  protected function setPaymentIndent(array $values) {
    $payment_intents = $this->entityTypeManager->getStorage('payment_intents')->create($values);
    $payment_intents->save();
  }
  
  /**
   * Validates the credit card form.
   * Une partie de la validation doit etre effectuer par stripe.js, mais on va
   * verifier quelques informations.
   *
   * @param array $element
   *        The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *        The current state of the complete form.
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    // on verifie que la methode de paiement a bien ete creer.
    if (empty($values['stripebyhabeuk_payment_method_id'])) {
      $form_state->setError($element['stripebyhabeuk_payment_method_id'], $this->t("Une erreur s'est produite, nous n'avons pas pu verifier votre Carte bancaire"));
    }
  }
  
  /**
   * Handles the submission of the credit card form.
   *
   * @param array $element
   *        The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *        The current state of the complete form.
   */
  protected function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // les informations de la carte vont etre recuperer à partir du serveur de
    // Stripe.
    // ( elles ont ete enregistrer via le js ).
  }
  
  /**
   * --
   */
  public static function trustedCallbacks() {
    parent::trustedCallbacks();
  }
  
}