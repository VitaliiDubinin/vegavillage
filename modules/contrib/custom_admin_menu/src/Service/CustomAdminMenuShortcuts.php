<?php

namespace Drupal\custom_admin_menu\Service;

use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manager service for shortcuts items in the toolbar.
 *
 * @package Drupal\custom_admin_menu\Service
 */
class CustomAdminMenuShortcuts {

  /**
   * Nom du service.
   *
   * @const string
   */
  const SERVICE_NAME = 'custom_admin_menu.shortcuts';

  /**
   * Theme Manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themManager;

  /**
   * Theme Initialisation.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialisation;

  /**
   * EntityTYpe Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Custom Menu Admin Settings.
   *
   * @var \Drupal\custom_admin_menu\Service\CustomAdminMenuSettings
   */
  protected $settings;

  /**
   * Theme System conf.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $themeSystemConf;

  /**
   * Admin theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $adminTheme;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * CustomAdminMenuShortcut constructor.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themManager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $themeInitialisation
   *   The theme initialisation.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config Factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request Stack.
   * @param \Drupal\Core\Controller\TitleResolverInterface $titleResolver
   *   The title resolver.
   * @param \Drupal\custom_admin_menu\Service\CustomAdminMenuSettings $settings
   *   The settings.
   */
  public function __construct(ThemeManagerInterface $themManager, ThemeInitializationInterface $themeInitialisation, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, RequestStack $requestStack, TitleResolverInterface $titleResolver, CustomAdminMenuSettings $settings) {
    $this->themManager = $themManager;
    $this->themeInitialisation = $themeInitialisation;
    $this->entityTypeManager = $entityTypeManager;
    $this->settings = $settings;
    $this->themeSystemConf = $configFactory->get('system.theme');
    $this->request = $requestStack->getCurrentRequest();
    $this->titleResolver = $titleResolver;
  }

  /**
   * Retourne le singleton.
   *
   * @return static
   *   Le singleton.
   */
  public static function me() {
    return \Drupal::service(static::SERVICE_NAME);
  }

  /**
   * Return the admin theme.
   *
   * @return \Drupal\Core\Theme\ActiveTheme
   *   The admin theme.
   */
  public function getAdminTheme() {
    if (is_null($this->adminTheme)) {
      $adminThemeName = $this->themeSystemConf->get('admin');
      $this->adminTheme = $this->themeInitialisation->initTheme($adminThemeName);
    }

    return $this->adminTheme;
  }

  /**
   * Return true if shortcut region is defined.
   *
   * @return bool
   *   The status.
   */
  public function hasShortcut() {
    return $this->settings->get(CustomAdminMenuSettings::FIELD_SHORTCUTS_REGION) != '';
  }

  /**
   * Get List of current admin theme.
   *
   * @return array
   *   Regions.
   */
  public function getShortcutRegions() {
    return system_region_list($this->getAdminTheme()->getName());
  }

  /**
   * Return the shortcuts region build array.
   *
   * @return array
   *   THe shortcut.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildShortcuts() {
    if (!$this->hasShortcut()) {
      return [];
    }

    $region = $this->settings->get(CustomAdminMenuSettings::FIELD_SHORTCUTS_REGION);
    $theme = $this->getAdminTheme();

    $blocks = $this->entityTypeManager->getStorage('block')->loadByProperties([
      'region' => $region,
      'theme'  => $theme->getName(),
    ]);

    $view_builder = $this->entityTypeManager->getViewBuilder('block');

    $build = [];

    $entity_type = $this->entityTypeManager->getDefinition('block');
    $cache_metadata = (new CacheableMetadata())
      ->addCacheTags($entity_type->getListCacheTags())
      ->addCacheContexts($entity_type->getListCacheContexts());

    /** @var \Drupal\block\BlockInterface[] $blocks */
    foreach ($blocks as $id => $block) {
      $access = $block->access('view', NULL, TRUE);
      $cache_metadata = $cache_metadata->merge(CacheableMetadata::createFromObject($access));
      if ($access->isAllowed()) {
        $block_plugin = $block->getPlugin();
        if ($block_plugin instanceof TitleBlockPluginInterface) {

          if ($route = $this->request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
            $block_plugin->setTitle($this->titleResolver
              ->getTitle($this->request, $route));
          }
        }
        $build['#blocks'][$id] = $view_builder->view($block);
      }
    }

    if ($build) {
      $build['#theme'] = 'custom_admin_menu_shortcuts';
    }

    $cache_metadata->applyTo($build);

    return $build;
  }

}
