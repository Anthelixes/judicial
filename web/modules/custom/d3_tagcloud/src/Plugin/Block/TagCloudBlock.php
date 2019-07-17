<?php

namespace Drupal\d3_tagcloud\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Leo Cloud block.
 *
 * @Block(
 *   id = "d3_tagcloud",
 *   admin_label = @Translation("D3 Tag Cloud"),
 *   category = @Translation("Tag Cloud for Taxonomy"),
 * )
 */
class TagCloudBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      '#markup' => 'This block show any taxonomy by select the name. ',
      '#attached' => [
        'library' => ['d3_tagcloud/leo-terms'],
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

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    /*
    $option = array(
      'show_check' => t('Show in tag cloud'),
      'field_show_in_term_cloud' => t('Show all items'),
    );

    $form['title_block_x'] = array(
      '#title' => t('What to display'),
      '#type' => 'checkboxes',
      '#description' => t('if you select "Show in tag cloud" it will be show only terms with ...'),
      '#options' => $option,
    );
     */
    $option1 = array(
      'taxonomy_thesaurus' => t('Thesaurus'),
      'taxonomy_topics' => t('Topics'),
    );
    $option2 = array(
      'show_check' => t('Show in tag cloud'),
      'field_show_in_term_cloud' => t('Show all items'),
    );

    $form['taxonomy_type'] = array(
      '#type' => 'radios',
      '#title' => t('What taxonomy?'),
      '#description' => t('Choose the taxonomy'),
      '#options' => $option1,
      '#required' => TRUE,
    );
    $form['type_display'] = array(
      '#type' => 'radios',
      '#title' => t('What to display'),
      '#description' => t('Show in tag cloud" it will be show only terms with ...'),
      '#options' => $option2,
      '#required' => TRUE,
    );
    return $form;
  }
}