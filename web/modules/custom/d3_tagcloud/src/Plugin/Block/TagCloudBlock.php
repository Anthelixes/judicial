<?php

namespace Drupal\d3_tagcloud\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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

  /**
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

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

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    $taxonomies = ['' => new TranslatableMarkup('-- Please select --')];
    foreach ($vocabularies as $machine_name => $voc) {
      $taxonomies[$machine_name] = $voc->label();
    }

    $form['taxonomy'] = array(
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Select taxonomy'),
      '#description' => new TranslatableMarkup('Taxonomy to display'),
      '#options' => $taxonomies,
      '#ajax' => [
        'callback' => [$this, 'myAjaxCallback'],
        'event' => 'change',
        'wrapper' => 'edit-output',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
      '#attributes' => [
      ],
      '#default_value' => $config['taxonomy'] ?? '',
      '#required' => TRUE,
    );

    $taxonomy = $config['taxonomy'];
    $form['visibility_field'] = $this->createVisibilityField($taxonomy);

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function createImportanceField($taxonomy) {

    $form['importance_field'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Importance field'),
      '#value' => '',
      '#options' => [0 => $this->t('-- None --')],
      '#attributes' => [
        'name' => 'field_select_importance',
      ],
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
      '#value' => !empty($config['importance_field']) ? $config['importance_field'] : 0,
    ];

    return $form['importance_field'];
  }

  /**
   * {@inheritdoc}
   */
  public function createVisibilityField($taxonomy) {
    $config = $this->getConfiguration();
    $fields = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $taxonomy);
    $options = ['' => new TranslatableMarkup('-- Please select --')];
    foreach ($fields as $field) {
      /** @var \Drupal\Core\Field\BaseFieldDefinition $field */
      $name = $field->getFieldStorageDefinition()->getName();

      if (substr($name, 0, 6) !== 'field_' && $name != 'name') {
        continue;
      }

      $options[$name] = $field->getLabel();
    }

    $form['visibility_field'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Visibility field'),
      '#value' => '',
      '#options' => [0 => $this->t('-- None --')],
      '#attributes' => [
        'name' => 'field_select_visibility',
      ],
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
      '#value' => !empty($config['visibility_field']) ? $config['visibility_field'] : 0,
    ];
    if (!empty($taxonomy)) {
      $form['visibility_field']['#options'] = $options;
    }
    else {
      $form['visibility_field']['#options'] = [0 => $this->t('-- None --')];
    }

    return $form['visibility_field'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $field = $form_state->getUserInput()['field_select_visibility'];

    $this->setConfigurationValue('taxonomy', $form_state->getValue('taxonomy'));
    $this->setConfigurationValue('visibility_field', $field);
  }

  /**
   * {@inheritdoc}
   */
  public function myAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $this->createVisibilityField($form_state->getValue('settings')['taxonomy']);
  }

}
