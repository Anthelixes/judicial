<?php

namespace Drupal\judicial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

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
        '#attributes' => ['class' => ['news-events-wrapper']],
        'news' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['news-wrapper']],
          'title' => [
            '#markup' => '<h2 class="block-title">' . t('Latest news') . '</h2>',
          ],
          'content' => views_embed_view('latest_news', 'latest_news_block'),
        ],
        'events' => [
          '#markup' => '<div class="events"><a href="' . Url::fromRoute('view.events.events_page')->toString() . '" class="btn-link-black events-text">' . t('See upcoming events') . '</a></div>',
        ],
      ]
    ];
  }

  public function getCacheMaxAge() {
    return 3600;
  }

}
