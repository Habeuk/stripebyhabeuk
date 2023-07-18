<?php

namespace Drupal\stripebyhabeuk\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\stripebyhabeuk\Entity\paymentIntentsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class paymentIntentsController.
 *
 *  Returns responses for Payment intents routes.
 */
class paymentIntentsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Payment intents revision.
   *
   * @param int $payment_intents_revision
   *   The Payment intents revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($payment_intents_revision) {
    $payment_intents = $this->entityTypeManager()->getStorage('payment_intents')
      ->loadRevision($payment_intents_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('payment_intents');

    return $view_builder->view($payment_intents);
  }

  /**
   * Page title callback for a Payment intents revision.
   *
   * @param int $payment_intents_revision
   *   The Payment intents revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($payment_intents_revision) {
    $payment_intents = $this->entityTypeManager()->getStorage('payment_intents')
      ->loadRevision($payment_intents_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $payment_intents->label(),
      '%date' => $this->dateFormatter->format($payment_intents->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Payment intents.
   *
   * @param \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface $payment_intents
   *   A Payment intents object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(paymentIntentsInterface $payment_intents) {
    $account = $this->currentUser();
    $payment_intents_storage = $this->entityTypeManager()->getStorage('payment_intents');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $payment_intents->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all payment intents revisions") || $account->hasPermission('administer payment intents entities')));
    $delete_permission = (($account->hasPermission("delete all payment intents revisions") || $account->hasPermission('administer payment intents entities')));

    $rows = [];

    $vids = $payment_intents_storage->revisionIds($payment_intents);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\stripebyhabeuk\Entity\paymentIntentsInterface $revision */
      $revision = $payment_intents_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $payment_intents->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.payment_intents.revision', [
            'payment_intents' => $payment_intents->id(),
            'payment_intents_revision' => $vid,
          ]))->toString();
        }
        else {
          $link = $payment_intents->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.payment_intents.revision_revert', [
                'payment_intents' => $payment_intents->id(),
                'payment_intents_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.payment_intents.revision_delete', [
                'payment_intents' => $payment_intents->id(),
                'payment_intents_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
    }

    $build['payment_intents_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
