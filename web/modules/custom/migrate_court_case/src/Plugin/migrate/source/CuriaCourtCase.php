<?php

namespace Drupal\migrate_court_case\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Source plugin for retrieving court cases via URLs.
 *
 * @MigrateSource(
 *   id = "curia_court_case"
 * )
 */
class CuriaCourtCase extends Url {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    /** @var \Drupal\migrate_court_case\Plugin\migrate_plus\data_parser\CuriaCourtCaseParser $plugin */
    $plugin = $this->getDataParserPlugin();
    $this->sourceUrls = [];
    foreach ($this->configuration['source_urls'] as $source_url) {
      $links = $plugin->getLinksFromUrl($source_url);
      foreach ($links as $link) {
        /** @var \DOMElement $link */
        if (in_array($link->getAttribute('href'), $this->sourceUrls)) {
          continue;
        }
        $this->sourceUrls[] = $link->getAttribute('href');
      }
    }
    $plugin->setUrls($this->sourceUrls);
  }

  public function count($refresh = FALSE) {
    return count($this->sourceUrls);
  }
}
