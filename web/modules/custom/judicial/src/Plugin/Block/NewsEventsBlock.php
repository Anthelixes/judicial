<?php

namespace Drupal\judicial\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'News and Events' Block.
 *
 * @Block(
 *   id = "frontpage_news_events",
 *   admin_label = @Translation("Frontpage news and events"),
 *   category = @Translation("Judicial"),
 * )
 */
class NewsEventsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'content' => [
        '#type' => 'container',
        'news' => views_embed_view('latest_news', 'latest_news_block'),
        'events' => [
          '#markup' => '<div class="events"><div class="events-text">' . t('See upcoming events') . '</div></div>',
        ],
      ]
    ];
  }

  public function getCacheMaxAge() {
    return 3600;
  }

}
