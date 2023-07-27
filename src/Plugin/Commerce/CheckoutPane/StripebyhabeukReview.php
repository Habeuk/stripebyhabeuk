<?php

namespace Drupal\stripebyhabeuk\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Permet d'effectuer le paiement sur Stripe.
 *
 * This checkout pane is required. It ensures that the last step in the checkout
 * performs authentication and confirmation of the payment intent. If the
 * customer's card is not enrolled in 3DS then the form will submit as normal.
 * Otherwise a modal will appear for the customer to authenticate and approve
 * of the charge.
 *
 * @CommerceCheckoutPane(
 *   id = "stripebyhabeuk_review",
 *   label = @Translation("Stripe By Habeuk review"),
 *   default_step = "review",
 *   wrapper_element = "container",
 * )
 */
class StripebyhabeukReview extends CheckoutPaneBase {
  
  /**
   * On initialise l'intention d payer sur le serveur et on renvoit le
   * client_secret du paiement.
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface::buildPaneForm()
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // on exsecute ceci c'est l'affichage du formulaire, une foix que
    // l'utilisateur aurra sousmis le formulaire, on paserra à l'etape suivante
    // si le paiement a reussi.
    if (!empty($form_state->getValues()) || !empty($form_state->getUserInput())) {
      return $pane_form;
    }
    $this->messenger()->addMessage("Load js for confirmation", true);
    /** @var \Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripebyhabeukStaticOnSite $stripe_plugin */
    $stripe_plugin = $this->order->get('payment_gateway')->entity->getPlugin();
    $PaymentIntent = $stripe_plugin->createPaymentIntent($this->order);
    $idHtml = !empty($pane_form['#id']) ? $pane_form['#id'] : Html::getUniqueId('cart-ifs-' . rand(100, 999));
    $pane_form['#attributes']['class'][] = 'kksd5764sdsedzsds';
    // To display validation errors.
    $pane_form['payment_errors'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      "#attributes" => [
        'id' => 'error-payment-confirm' . $idHtml,
        'class' => []
      ]
    ];
    // permet de sauvegarder l'id de PaymentIntent.
    $pane_form['stripebyhabeuk_payment_intent_id'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'payment-intent-id' . $idHtml
      ]
    ];
    
    $url = Url::fromRouteMatch(\Drupal::routeMatch());
    $url->setAbsolute(true);
    // pass and attach datas.
    $pane_form['#attached']['library'][] = 'stripebyhabeuk/stripejsinit';
    $pane_form['#attached']['drupalSettings']['stripebyhabeuk'] = [
      'publishableKey' => $stripe_plugin->getPublishableKey(),
      'payment_status' => $PaymentIntent->status,
      'idhtml' => $idHtml,
      'clientSecret' => $PaymentIntent->client_secret,
      'return_url' => $url->toString()
    ];
    
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->order);
    $cacheability->setCacheMaxAge(0);
    $cacheability->applyTo($pane_form);
    return $pane_form;
  }
  
}