<?php

namespace Drupal\stripebyhabeuk\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ReliquatToPaidController.
 *
 *  Returns responses for Reliquat to paid routes.
 */
class ReliquatToPaidController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a Reliquat to paid revision.
   *
   * @param int $reliquat_to_paid_revision
   *   The Reliquat to paid revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($reliquat_to_paid_revision) {
    $reliquat_to_paid = $this->entityTypeManager()->getStorage('reliquat_to_paid')
      ->loadRevision($reliquat_to_paid_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('reliquat_to_paid');

    return $view_builder->view($reliquat_to_paid);
  }

  /**
   * Page title callback for a Reliquat to paid revision.
   *
   * @param int $reliquat_to_paid_revision
   *   The Reliquat to paid revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($reliquat_to_paid_revision) {
    $reliquat_to_paid = $this->entityTypeManager()->getStorage('reliquat_to_paid')
      ->loadRevision($reliquat_to_paid_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $reliquat_to_paid->label(),
      '%date' => $this->dateFormatter->format($reliquat_to_paid->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Reliquat to paid.
   *
   * @param \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface $reliquat_to_paid
   *   A Reliquat to paid object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ReliquatToPaidInterface $reliquat_to_paid) {
    $account = $this->currentUser();
    $reliquat_to_paid_storage = $this->entityTypeManager()->getStorage('reliquat_to_paid');

    $langcode = $reliquat_to_paid->language()->getId();
    $langname = $reliquat_to_paid->language()->getName();
    $languages = $reliquat_to_paid->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $reliquat_to_paid->label()]) : $this->t('Revisions for %title', ['%title' => $reliquat_to_paid->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all reliquat to paid revisions") || $account->hasPermission('administer reliquat to paid entities')));
    $delete_permission = (($account->hasPermission("delete all reliquat to paid revisions") || $account->hasPermission('administer reliquat to paid entities')));

    $rows = [];

    $vids = $reliquat_to_paid_storage->revisionIds($reliquat_to_paid);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\stripebyhabeuk\Entity\ReliquatToPaidInterface $revision */
      $revision = $reliquat_to_paid_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $reliquat_to_paid->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.reliquat_to_paid.revision', [
            'reliquat_to_paid' => $reliquat_to_paid->id(),
            'reliquat_to_paid_revision' => $vid,
          ]))->toString();
        }
        else {
          $link = $reliquat_to_paid->toLink($date)->toString();
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
              'url' => $has_translations ?
              Url::fromRoute('entity.reliquat_to_paid.translation_revert', [
                'reliquat_to_paid' => $reliquat_to_paid->id(),
                'reliquat_to_paid_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.reliquat_to_paid.revision_revert', [
                'reliquat_to_paid' => $reliquat_to_paid->id(),
                'reliquat_to_paid_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.reliquat_to_paid.revision_delete', [
                'reliquat_to_paid' => $reliquat_to_paid->id(),
                'reliquat_to_paid_revision' => $vid,
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
    }

    $build['reliquat_to_paid_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
