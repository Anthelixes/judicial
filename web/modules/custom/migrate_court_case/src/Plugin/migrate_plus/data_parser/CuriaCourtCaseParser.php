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

  protected function getValueFromXpath(\SimpleXMLElement $xml_element, $xpath) {
    $value = $xml_element->xpath($xpath);
    $value = reset($value);
    return $value->__toString();
  }

  protected function parseNotice($url) {
    $xml = simplexml_load_string($this->getDataFetcherPlugin()->getResponseContent($url)->getContents());

    $title = $this->getValueFromXpath($xml, '//NOTICE/EXPRESSION/EXPRESSION_TITLE/VALUE');
    $country_iso3 = $this->getValueFromXpath($xml, '//CASE-LAW_ORIGINATES_IN_COUNTRY/OP-CODE');
    $date = $this->getValueFromXpath($xml, '//WORK_DATE_DOCUMENT/VALUE');

    return [
      'title' => $title,
      'country_iso3' => $country_iso3,
      'date' => $date,
    ];
  }

  protected function parseBody($body_url) {
    $html = $this->getSourceData($body_url);
    $body = $html->getElementsByTagName('body')->item(0);
    $body = $html->saveHTML($body);
    $pattern = '/<a .*?<\/a>/';
    $body = preg_replace($pattern, "", $body);
    $body = str_replace('<em class="none">|</em>', '', $body);
    return $body;
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
      $body = $this->parseBody($html_body_url);

      $notice_url = $case_html->getElementById('link-download-notice')->getAttribute('href');
      $notice_url = str_replace('./../../../', 'https://eur-lex.europa.eu/', $notice_url);

      $this->currentItem = [
        'case_number' => $case_number,
        'body' => $body,
        'reference_number' => $case_number,
        'external_link' => $html_case_url,
        'case_source' => 'EUR-Lex',
      ];
      $this->currentItem += $this->parseNotice($notice_url);

      $this->iterator->next();
    }
  }

}
