<?php

namespace Drupal\stripebyhabeuk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;

/**
 * Returns responses for stripe by habeuk routes.
 */
class StripebyhabeukController extends ControllerBase {
  
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
