<?php

namespace Drupal\judicial\Plugin\facets\processor;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Result\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform langcode to langauage name.
 *
 * @FacetsProcessor(
 *   id = "langcode",
 *   label = @Translation("Transform langcode to langauage name"),
 *   stages = {
 *     "build" = 100
 *   }
 * )
 */
class LangcodeProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    foreach ($results as $id => $result) {
      /** @var Result $result */
      $langcode = $result->getRawValue();
      $language = $this->languageManager->getLanguage($langcode);
      if (empty($language)) {
        continue;
      }
      $result->setDisplayValue($language->getName());
    }

    return $results;
  }

}
