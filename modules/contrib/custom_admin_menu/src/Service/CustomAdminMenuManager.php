<?php

namespace Drupal\custom_admin_menu\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Container;

/**
 * Service offering tools to render custom admin menu in toolbar.
 *
 * @package Drupal\custom_admin_menu\Service
 */
class CustomAdminMenuManager implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * Nom du service.
   *
   * @const string
   */
  const SERVICE_NAME = 'custom_admin_menu.manager';

  /**
   * Custom Menu Name.
   *
   * @const string
   */
  const CUSTOM_MENU_NAME = 'custom_admin_menu';

  /**
   * Menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Theme admin.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $adminTheme;

  /**
   * Theme Manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Theme Systeme conf.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $themeSystemConf;

  /**
   * Theme initialisation.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The settings.
   *
   * @var \Drupal\custom_admin_menu\Service\CustomAdminMenuSettings
   */
  protected $settings;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Menu item display manager.
   *
   * @var \Drupal\custom_admin_menu\Service\CustomAdminMenuMenuItemDisplayManager
   */
  protected CustomAdminMenuMenuItemDisplayManager $customAdminMenuMenuItemDisplayManager;

  /**
   * CustomAdminMenuManager constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   The menu link tree.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config Factory.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $themeInitialization
   *   The theme Initialisation.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\custom_admin_menu\Service\CustomAdminMenuSettings $settings
   *   The settings.
   */
  public function __construct(
    AccountInterface $currentUser,
    MenuLinkTreeInterface $menuLinkTree,
    ThemeManagerInterface $themeManager,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    ThemeInitializationInterface $themeInitialization,
    ModuleHandlerInterface $moduleHandler,
    CustomAdminMenuSettings $settings,
    CustomAdminMenuMenuItemDisplayManager $customAdminMenuMenuItemDisplayManager) {
    $this->currentUser = $currentUser;
    $this->menuLinkTree = $menuLinkTree;
    $this->adminTheme = $themeManager->getActiveTheme();
    $this->themeManager = $themeManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->themeSystemConf = $configFactory->get('system.theme');
    $this->themeInitialization = $themeInitialization;
    $this->moduleHandler = $moduleHandler;
    $this->settings = $settings;
    $this->customAdminMenuMenuItemDisplayManager = $customAdminMenuMenuItemDisplayManager;
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
   * Check if use can see Default Admin Menu.
   *
   * @return bool
   *   The status.
   */
  public function userCanSeeDefaultAdminMenu() {
    return $this->currentUser->hasPermission('access_default_menu');
  }

  /**
   * Check if user can see custom admin menu.
   *
   * @return bool
   *   The status.
   */
  public function userCanSeeCustomAdminMenu() {
    return $this->currentUser->hasPermission('access_custom_menu');
  }

  /**
   * Return the custom menu content.
   */
  public function getCustomMenuContent() {
    // Get the menu.
    $menu = $this->entityTypeManager->getStorage('menu')
      ->load($this->getCustomMenuName());

    // Build the menu.
    $buildArray = $this->getMenuBuildArray($menu->id());

    // Filter allowed items.
    $this->customAdminMenuMenuItemDisplayManager->filterItems($buildArray['#items']);

    // Add toolbar-icon class.
    $this->initClasses($buildArray['#items']);

    // Alter custom menu build array.
    $this->moduleHandler->alter('custom_admin_menu', $buildArray);
    $this->themeManager->alter('custom_admin_menu', $buildArray);

    return $buildArray;
  }

  /**
   * Return the menu name.
   *
   * @return string
   *   The menu name.
   */
  public function getCustomMenuName() {
    return static::CUSTOM_MENU_NAME;
  }

  /**
   * Return a menu build array.
   *
   * @param string $menuId
   *   L'id du menu.
   * @param array|\string[][] $manipulators
   *   Les manipulators.
   *
   * @return array
   *   Le markup.
   */
  public function getMenuBuildArray(string $menuId, array $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ]): array {

    $parameters = new MenuTreeParameters();
    $parameters->excludeRoot()->onlyEnabledLinks();
    $tree = $this->menuLinkTree->load($menuId, $parameters);

    $buildElements = $this->menuLinkTree->transform($tree, $manipulators);

    return $this->menuLinkTree->build($buildElements);
  }

  /**
   * Add default classes.
   *
   * @param array $links
   *   Links.
   */
  protected function initClasses(array $links = []) {
    foreach ($links as $item) {
      $id = $item['original_link']->getPluginId();
      $suffix = explode('.', $id);
      $id = str_replace('.', '-', Container::underscore($item['original_link']->getPluginId()));
      /** @var \Drupal\Core\Url $url */
      if ($url = $item['url']) {
        $url->setOption('attributes', [
          'class' => [
            'toolbar-icon',
            'toolbar-icon-' . $id,
          ],
        ]);
      }

      // Modules and themes can update custom menu items element.
      $context = [
        'plugin_id' => $id,
        'suffix'    => end($suffix),
      ];
      $this->moduleHandler
        ->alter('custom_admin_menu_item', $item, $context);
      $this->themeManager->alter('custom_admin_menu_item', $item, $context);

      if (isset($item['below']) && is_array($item['below'])) {
        $this->initClasses($item['below']);
      }
    }
  }

  /**
   * Ajoute le menu contrib aux items de la toolbar.
   *
   * @param array $items
   *   Les items du menu.
   */
  public function addCustomAdminMenuEntries(array &$items): void {
    $menu = $this->entityTypeManager->getStorage('menu')
      ->load($this->getCustomMenuName());

    if (!$menu) {
      return;
    }

    $menuName = $this->getCustomMenuName();
    $items[$menuName] = [
      '#attributes' => ['class' => [$menuName]],
      '#type'       => 'toolbar_item',
      '#weight'     => 0,
      'tab'         => [
        '#type'       => 'link',
        '#title'      => $this->t($menuName),
        '#url'        => Url::fromRoute('<front>'),
        '#attributes' => [
          'title' => $menu->label(),
          'class' => ['toolbar-icon', 'toolbar-icon-system-admin-content'],
        ],
      ],
      'tray'        => [
        '#heading'                             => $menu->label(),
        '#attached'                            => [
          'library' => [
            'admin_toolbar/toolbar.tree',
          ],
        ],
        'toolbar_ui_additions_toolbar_content' => [
          '#pre_render' => [
            [static::class, 'buildCustomToolbarContent'],
          ],
          [
            '#type'       => 'container',
            '#attributes' => [
              'class' => ['toolbar-menu-administration'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Retourne le contenu du Contrbi menu de la toolbar.
   *
   * @param array $element
   *   L'element en train d'être buildé.
   *
   * @return array
   *   Le build.
   */
  public static function buildCustomToolbarContent(array $element): array {
    return static::me()->getMenuBuildArray(static::me()->getCustomMenuName());
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'buildCustomToolbarContent',
    ];
  }

}
