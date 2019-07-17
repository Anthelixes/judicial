<?php

namespace Drupal\judicial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Jurisprudence per region' Block.
 *
 * @Block(
 *   id = "jurisprudence_per_region",
 *   admin_label = @Translation("Jurisprudence per region"),
 *   category = @Translation("Judicial"),
 * )
 */
class JurisprudencePerRegionBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    return [
      'europe' => [
        'text' => ['#markup' => '<div class="text europe-text">' . t('Europe') . '</div>'],
        'count' => ['#markup' => '<div class="count europe-count">' . $this->getCourtCaseCountPerContinent('EU') . '</div>'],
      ],
      'asia' => [
        'text' => ['#markup' => '<div class="text asia-text">' . t('Asia') . '</div>'],
        'count' => ['#markup' => '<div class="count asia-count">' . $this->getCourtCaseCountPerContinent('AS') . '</div>'],
      ],
      'africa' => [
        'text' => ['#markup' => '<div class="text africa-text">' . t('Africa') . '</div>'],
        'count' => ['#markup' => '<div class="count africa-count">' . $this->getCourtCaseCountPerContinent('AF') . '</div>'],
      ],
      'north_america' => [
        'text' => ['#markup' => '<div class="text north-america-text">' . t('North America') . '</div>'],
        'count' => ['#markup' => '<div class="count north-america-count">' . $this->getCourtCaseCountPerContinent('NA') . '</div>'],
      ],
      'south_america' => [
        'text' => ['#markup' => '<div class="text south-america-text">' . t('South America') . '</div>'],
        'count' => ['#markup' => '<div class="count south-america-count">' . $this->getCourtCaseCountPerContinent('SA') . '</div>'],
      ],

      'image' => [
        '#markup' => '<img class="img img-responsive" src="/modules/custom/judicial/data/region-data.png">'
      ],
    ];
  }

  public function getCacheMaxAge() {
    return 0;
  }

  protected function getCourtCaseCountPerContinent($continent) {
    $tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', 'countries')
      ->condition('field_continent', $continent)
      ->execute();
    return floor(count($tids) / 10) * 10 . '+';
  }

}
