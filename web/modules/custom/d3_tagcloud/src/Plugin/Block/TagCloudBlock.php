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

  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list']);
  }

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
        'callback' => '::myAjaxCallback', //don't forget :: when calling a class method.
        //'callback' => [$this, 'myAjaxCallback'], //alternative notation
        'event' => 'change',
        'wrapper' => 'edit-output', //this element is updated with this AJAX callback
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
      '#default_value' => $config['taxonomy'] ?? '',
      '#required' => TRUE,
    );

    $tax = 'thesaurus';
    $fields = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $tax);

    $options = ['' => new TranslatableMarkup('-- Please select --')];
    foreach ($fields as $field) {
      /** @var \Drupal\Core\Field\BaseFieldDefinition $field */

      $options[$field->getUniqueIdentifier()] = $field->getLabel();
    }

    $form['visibility_field'] = array(
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Visibility field'),
      '#value' => '',
      '#options' => $options,
      '#states' => [
        'visible' => [
          ':input[name="settings[taxonomy]"]' => ['!value' => ''],
        ],
      ],
      '#attributes' => [
        'id' => ['edit-output'],
      ],
    );

    return $form;
  }
  function my_module_my_form_validate(array &$form, FormStateInterface $form_state){
    $tax = $form_state['values']['taxonomy'];
    return $tax;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $config['taxonomy'] = $form_state->getValue('taxonomy');
    $config['visibility_field'] = $form_state->getValue('visibility_field');

  }

  //get the value from example select field and fill
  //the textbox with the selected text.
  public function myAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    //prepare our textfield. check if the example select field has a selected option
    if($selectedValue = $form_state->getValue('taxonomy')) {
      $selectedText = $form['taxonomy']['#options'][$selectedValue];

      $form['visibility_field']['#value'] = $selectedText;
    }
    return $form['visibility_field'];
  }
}