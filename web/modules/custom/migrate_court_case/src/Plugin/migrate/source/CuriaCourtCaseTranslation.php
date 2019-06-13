<?php

namespace Drupal\migrate_court_case\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin for retrieving court cases via URLs.
 *
 * @MigrateSource(
 *   id = "curia_court_case_translation"
 * )
 */
class CuriaCourtCaseTranslation extends Url {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    /** @var \Drupal\migrate_court_case\Plugin\migrate_plus\data_parser\CuriaCourtCaseParser $plugin */
    $plugin = $this->getDataParserPlugin();
    $this->sourceUrls = [];
    $cases = $this->getCuriaCases();

    $data = [];
    foreach ($cases as $case) {
      $languages = $case->field_languages->getValue();
      if (empty($languages)) {
        continue;
      }
      $case_number = $case->field_reference_number->value;
      $link = "https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=$case_number";

      foreach ($languages as $language) {
        $language = $language['value'];
        $this->sourceUrls[] = str_replace('/FR/', '/' . strtoupper($language) . '/', $link);
        $data[] = [
          'nid' => $case->id(),
          'langcode' => $language,
        ];
      }
    }
    $plugin->setUrls($this->sourceUrls);
    $plugin->setUrlData($data);
  }

  public function count($refresh = FALSE) {
    return count($this->sourceUrls);
  }

  protected function getCuriaCases() {
    $eurlex = taxonomy_term_load_multiple_by_name('EUR-Lex', 'data_sources');
    $eurlex = reset($eurlex)->id();

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $node_storage->getQuery();
    $nids = $query->condition('type', 'court_case')
      ->condition('status', '1')
      ->condition('field_sources', $eurlex)
      ->execute();

    return $node_storage->loadMultiple($nids);
  }

}
