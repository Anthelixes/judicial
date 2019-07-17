<?php

namespace Drupal\judicial\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use \Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Provides a 'Thesaurus tabs' Block.
 *
 * @Block(
 *   id = "judicial_thesaurus",
 *   admin_label = @Translation("Thesaurus tabs"),
 *   category = @Translation("Judicial"),
 * )
 */
class ThesaurusBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $alphabetically_page_link = Url::fromRoute('view.thesaurus.page_alphabetically')->toString();
    $topic_page_link = Url::fromRoute('view.glossary.page_1')->toString();
    $download_link = Url::fromRoute('judicial.download_xls')->toString();
    $topic_link_classes = ['glossary-tab', 'glossary-topic-tab'];
    $alphabetically_link_classes = ['glossary-tab', 'glossary-alphabetically-tab'];
    // Set the active element
    if ('view.thesaurus.page_alphabetically' == $this->routeMatch->getRouteName()) {
      $alphabetically_link_classes[] = 'active';
    }
    else {
      $topic_link_classes[] = 'active';
    }
    return [
      '#theme' => 'gpthesaurus_list_type_switcher_block',
      '#alphabetically_page_link' => $alphabetically_page_link,
      '#alphabetically_link_classes' => $alphabetically_link_classes,
      '#topic_page_link' => $topic_page_link,
      '#topic_link_classes' => $topic_link_classes,
      '#download_link' => $download_link,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->routeMatch->getRouteName() == 'entity.taxonomy_term.canonical'
      && $tid =$this->routeMatch->getRawParameter('taxonomy_term')
    ) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
      if ($term->bundle() == 'thesaurus') {
        return AccessResult::forbidden();
      }
    }
    return parent::blockAccess($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }
}
