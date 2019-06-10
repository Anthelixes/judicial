<?php

namespace Drupal\migrate_court_case\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\DataParserPluginBase;

/**
 * Obtain court cases from CURIA HTML.
 *
 * @DataParser(
 *   id = "curia",
 *   title = @Translation("CURIA")
 * )
 */
class CuriaCourtCaseParser extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Iterator over the DOMDocument.
   *
   * @var \Iterator
   */
  protected $iterator;

  /**
   * Retrieves the HTML data and returns it as a DOMDocument.
   *
   * @param string $url
   *   URL of a feed.
   *
   * @return \DOMDocument
   *   The selected data.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function getSourceData($url) {
    /** @var \GuzzleHttp\Psr7\Stream $response */
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    $dom = new \DOMDocument();
    @$dom->loadHTML($response->getContents());

    return $dom;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
    $source_data = $this->getSourceData($url);
    $links = $source_data->getElementsByTagName('a');

    $this->iterator = new \ArrayIterator(iterator_to_array($links));

    return TRUE;
  }

  protected function parseNotice($url) {
    $xml = simplexml_load_string($this->getDataFetcherPlugin()->getResponseContent($url)->getContents());

    $title = $xml->xpath('//NOTICE/EXPRESSION/TITLE/VALUE');
    $title = reset($title);
    /** @var \SimpleXMLElement $body */
    $title = $title->__toString();

    return [
      'title' => $title,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    $current = $this->iterator->current();

    if ($current) {
      $url = $current->getAttribute('href');
      $parts = parse_url($url);
      parse_str($parts['query'], $query);
      $case_number = $query['uri'];

      $html_case_url = "https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=$case_number";
      $case_html = $this->getSourceData($html_case_url);

      $html_body_url = "https://eur-lex.europa.eu/legal-content/FR/TXT/HTML/?uri=$case_number";

      $notice_url = $case_html->getElementById('link-download-notice')->getAttribute('href');
      $notice_url = str_replace('./../../../', 'https://eur-lex.europa.eu/', $notice_url);

      $body = $this->getSourceData($html_body_url)->getElementsByTagName('body')->item(0)->nodeValue;

      $this->currentItem = $this->parseNotice($notice_url) + ['case_number' => $case_number, 'body' => $body];

      $this->iterator->next();
    }
  }

}
