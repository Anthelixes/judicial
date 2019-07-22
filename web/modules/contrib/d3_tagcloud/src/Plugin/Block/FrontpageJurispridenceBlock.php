<?php

namespace Drupal\judicial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'Frontpage Jurisprudence' Block.
 *
 * @Block(
 *   id = "frontpage_jurisprudence",
 *   admin_label = @Translation("Frontpage Jurisprudence"),
 *   category = @Translation("Judicial"),
 * )
 */
class FrontpageJurispridenceBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $plugin_block = $block_manager->createInstance('jurisprudence_per_region', $config);
    $render = $plugin_block->build();

    return [
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['two-block-wrapper frontpage-jurisprudence-wrapper']],
        'view' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['left-wrapper jurisprudence-wrapper']],
          'title' => [
            '#markup' => '<h2 class="block-title">' . t('Jurisprudence') . '</h2>',
          ],
          'content' => views_embed_view('jurisprudence_no_index', 'frontpage'),
        ],
        'chart' => [
          'content' => $render,
          '#prefix' => '<div id= "block-jurisprudenceperregion" class="right-wrapper chart">',
          '#suffix' => '</div>',
        ],
      ]
    ];
  }

  public function getCacheMaxAge() {
    return 0;
  }

}
