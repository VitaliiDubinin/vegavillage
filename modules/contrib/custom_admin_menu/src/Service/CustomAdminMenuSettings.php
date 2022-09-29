<?php

namespace Drupal\custom_admin_menu\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for settings storage.
 *
 * @package Drupal\custom_admin_menu\Service
 */
class CustomAdminMenuSettings {

  /**
   * Nom du service.
   *
   * @const string
   */
  const SERVICE_NAME = 'custom_admin_menu.settings';

  /**
   * Enable custom menu.
   *
   * @const string
   */
  public const FIELD_ENABLE = 'enable';

  /**
   * FIeld Insertion type.
   *
   * @const string
   */
  const FIELD_INSERTION_TYPE = 'insertion_type';

  /**
   * Field append to admin items.
   *
   * @const string
   */
  const INSERTION_TYPE_APPEND = 'append';

  /**
   * Field prepend to admin items.
   *
   * @const string
   */
  const INSERTION_TYPE_PREPEND = 'prepend';

  /**
   * Wrap.
   *
   * @const string
   */
  const FIELD_WRAP_DEFAULT_ADMIN = 'wrap_admin';

  /**
   * Separate Menu.
   *
   * @const string
   */
  const FIELD_INCLUDED_IN_ADMIN = 'include_in_admin';

  /**
   * Quick Edit Shortcuts.
   *
   * @const string
   */
  const FIELD_SHORTCUTS_REGION = 'shortcuts_region';

  /**
   * Immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $editable;

  /**
   * Update.
   *
   * @var bool
   */
  protected $updateNeeded;

  /**
   * Config constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get(static::SERVICE_NAME);
    $this->editable = $configFactory->getEditable(static::SERVICE_NAME);
  }

  /**
   * Return the singleton.
   *
   * @return static
   *   The singleton.
   */
  public static function me() {
    return \Drupal::service(static::SERVICE_NAME);
  }

  /**
   * Return the value of a specific config item.
   *
   * @param string|null $key
   *   The config key.
   *
   * @return array|mixed|null
   *   The value.
   */
  public function get($key = NULL) {
    if ($key) {
      return $this->config->get($key);
    }

    return $this->config->getRawData();
  }

  /**
   * Modifie la valeur.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   */
  public function set($key, $value) {
    $this->updateNeeded = TRUE;
    $this->editable->set($key, $value);

    return $this;
  }

  /**
   * Save.
   */
  public function save() {
    if ($this->updateNeeded) {
      $this->editable->save();
    }

    return $this;
  }

}
