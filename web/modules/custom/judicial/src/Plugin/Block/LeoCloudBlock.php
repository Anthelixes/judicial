<?php

namespace Drupal\judicial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Leo Cloud block.
 *
 * @Block(
 *   id = "leo_cloud",
 *   admin_label = @Translation("Leo Cloud"),
 *   category = @Translation("Judicial"),
 * )
 */
class LeoCloudBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $vid = 'thesaurus';
    $render = [];
    $tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', $vid)
      ->condition('field_show_in_term_cloud', 1)
      ->execute();
    $terms = Term::loadMultiple($tids);
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $link = $term->toUrl()->toString();

      $render[] = [
        'tid' => $term->id(),
        'text' => $term->label(),
        'link' => $link,
        'importance' => $term->get('field_importance')->getString(),
      ];
    }
    return [
      '#markup' => ' ',
      '#attached' => [
        'library' => ['judicial/leo-terms'],
        'drupalSettings' => [
          'leoTerms' => [
            'termsList' => $render,
          ],
        ],
      ],
    ];
  }

  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list']);
  }

}
