<?php

namespace Drupal\migrate_court_case\Plugin\migrate\source;

use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Source plugin for retrieving court cases via URLs.
 *
 * @MigrateSource(
 *   id = "curia_court_case"
 * )
 */
class CuriaCourtCase extends Url {

  public function count($refresh = FALSE) {
    $count = 0;
    foreach ($this->sourceUrls as $source_url) {
      /** @var \Drupal\migrate_court_case\Plugin\migrate_plus\data_parser\CuriaCourtCaseParser $parser */
      $parser = $this->getDataParserPlugin();

      $links = $parser->getLinksFromUrl($source_url);
      $count += count($links);
    }

    return $count;
  }
}
