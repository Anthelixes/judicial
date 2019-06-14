<?php

namespace Drupal\judicial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides the Leo Cloud block.
 *
 * @Block(
 *   id = "leo_cloud",
 *   admin_label = @Translation("Leo Cloud"),
 *   category = @Translation("Judicial"),
 * )
 */
class LeoCloudBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $vid = 'glossary_terms';
    $render = [];
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->condition('field_show_in_term_cloud', 1)
      ->execute();
    $terms = Term::loadMultiple($tids);
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $link = $term->toUrl();

      $render[] = [
        'tid' => $term->id(),
        'text' => $term->label(),
        'link' => $link,
        'importance' => $term->get('field_importance')->getString(),
      ];
    }
    return array(
      '#markup' => ' ',
      '#attached' => [
        'library' => ['judicial/leo-terms'],
        'drupalSettings' => [
          'leoTerms' => [
            'termsList' => $render,
          ],
        ],
      ],
    );
  }

}
