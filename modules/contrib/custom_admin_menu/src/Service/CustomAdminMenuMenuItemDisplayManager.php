<?php

namespace Drupal\custom_admin_menu\Service;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

/**
 * Provides tools to hide/display menu items
 */
class CustomAdminMenuMenuItemDisplayManager {

  /**
   * Nom du service.
   *
   * @const string
   */
  const SERVICE_NAME = 'custom_admin_menu.menu_item_display_manager';

  /**
   * Current User.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Current language id.
   *
   * @var string
   */
  protected $currentLanguageId;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   */
  public function __construct(AccountProxyInterface $currentUser, LanguageManagerInterface $languageManager) {
    $this->currentUser = $currentUser;
    $this->currentLanguageId = $languageManager->getCurrentLanguage()->getId();
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
   * {@inheritdoc}
   */
  public function initMenuItemsDisplayState(&$variables) {
    foreach ($variables as $pluginId => &$item) {
      if ($this->itemIsDisallowed($item)) {
        unset($variables[$pluginId]);
      }
      else {
        if (isset($item['below'])) {
          $this->initMenuItemsDisplayState($item['below']);
        }
      }
    }
  }

  /**
   * Return true if item is explicitly disallowed.
   *
   * @param $pluginDefinition
   *   The item plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The user.
   *
   * @return bool
   *   The state.
   */
  protected function itemIsDisallowed($item, AccountProxyInterface $user = NULL) {
    $user = $user ?: $this->currentUser;
    $isDisallowed = FALSE;
    // Super user.
    if ($this->isSuperUser($user)) {
      return $isDisallowed;
    }

    if (isset($item['original_link'])) {
      $pluginDefinition = $item['original_link']->getPluginDefinition();

      if ($pluginDefinition['menu_name'] !== CustomAdminMenuManager::CUSTOM_MENU_NAME) {
        return FALSE;
      }

      if (!empty($pluginDefinition)) {
        $metadata = isset($pluginDefinition['metadata']) ? $pluginDefinition['metadata'] : [];
        if (!empty($metadata)) {
          $isDisallowed =
            $this->isDisallowedRoles($metadata, $user)
            || $this->isDisallowedLanguage($metadata);
        }
      }
    }

    return $isDisallowed;
  }

  /**
   * Check item roles.
   *
   * @param array $pluginDefinition
   *   The item plugin definition.
   * @param AccountProxyInterface $user
   *   The user;
   *
   * @return bool
   */
  protected function isDisallowedRoles(array $metadata, AccountProxyInterface $user) {
    $isDisallowed = FALSE;

    if (isset($metadata['disallowed_roles']) && !empty($metadata['disallowed_roles'])) {
      $isDisallowed = 0 < count(
          array_intersect($user->getRoles(), $metadata['disallowed_roles'])
        );
    }
    elseif (isset($metadata['need_roles']) && !empty($metadata['need_roles'])) {
      $isDisallowed = 0 == count((
        array_intersect($user->getRoles(), $metadata['need_roles'])
        ));
    }

    return $isDisallowed;
  }

  protected function isDisallowedLanguage(array $metadata) {
    $isDisallowed = FALSE;

    if (isset($metadata['allowed_languages']) && !empty($metadata['allowed_languages'])) {
      $isDisallowed = !in_array($this->currentLanguageId, $metadata['allowed_languages']);
    }

    return $isDisallowed;
  }

  /**
   * Filter item access.
   *
   * @param array $items
   *   THe list of menu items.
   */
  public function filterItems(&$items) {
    $items = array_filter($items, function ($item) {
      return !$this->itemIsDisallowed($item);
    });

    foreach ($items as &$item) {
      if (isset($item['below']) && is_array($item['below']) && !empty($item['below'])) {
        $this->filterItems($item['below']);
      }
    }
  }

  /**
   * Return true if user is super user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface|NULL $user
   *   The user.
   *
   * @return bool
   *   The user is super user;
   */
  public function isSuperUser(AccountProxyInterface $user = NULL) {
    $user = $user ?: $this->currentUser;
    return $user->id() == 1;
  }

}
