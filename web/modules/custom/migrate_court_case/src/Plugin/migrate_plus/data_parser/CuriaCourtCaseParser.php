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
   * @var array
   */
  protected $urlData;

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
  public function getSourceData($url) {
    /** @var \GuzzleHttp\Psr7\Stream $response */
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    $dom = new \DOMDocument();
    @$dom->loadHTML($response->getContents());

    return $dom;
  }

  public function getLinksFromUrl($url) {
    $source_data = $this->getSourceData($url);
    $links = $source_data->getElementsByTagName('a');
    return iterator_to_array($links);
  }

  public function setUrls($urls) {
    $this->urls = $urls;
    $this->activeUrl = 0;
  }

  public function setUrlData($data) {
    $this->urlData = $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
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

  public function getAvailableLanguages($html_url) {
    $body = $this->getSourceData($html_url);

    $xpath = new \DOMXPath($body);
    $available_languages = [];
    foreach (['en', 'es', 'ar', 'ru', 'zh-hans'] as $language) {
      @$entries = $xpath->query('//ul[@class="dropdown-menu PubFormatHTML"]/li/a[@lang="' . $language . '"]');
      if ($entries->length > 0) {
        $available_languages[] = $language;
      }
    }

    return $available_languages;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    $url = $this->urls[$this->activeUrl];
    $parts = parse_url($url);
    parse_str($parts['query'], $query);
    $case_number = $query['uri'];

    $langcode = !empty($this->urlData[$this->activeUrl]['langcode']) ? strtoupper($this->urlData[$this->activeUrl]['langcode']) : 'FR';

    $html_case_url = "https://eur-lex.europa.eu/legal-content/$langcode/TXT/?uri=$case_number";
    $case_html = $this->getSourceData($html_case_url);


    $html_body_url = "https://eur-lex.europa.eu/legal-content/$langcode/TXT/HTML/?uri=$case_number";
    $body = $this->parseBody($html_body_url);

    $notice_url = $case_html->getElementById('link-download-notice')->getAttribute('href');
    $notice_url = str_replace('./../../../', 'https://eur-lex.europa.eu/', $notice_url);

    $this->currentItem = [
      'case_number' => $case_number,
      'body' => $body,
      'reference_number' => $case_number,
      'external_link' => $html_case_url,
      'case_source' => 'EUR-Lex',
      'langcode' => strtolower($langcode),
    ];
    if (!empty($this->urlData[$this->activeUrl]['nid'])) {
      $this->currentItem['nid'] = $this->urlData[$this->activeUrl]['nid'];
    }

    $this->currentItem += $this->parseNotice($notice_url);
  }

}
