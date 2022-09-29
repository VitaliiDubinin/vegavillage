<?php

namespace Drupal\custom_admin_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Custom Admin Menu routes.
 */
class CustomAdminMenuController extends ControllerBase {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * ToolbarRedirectController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityType manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The requestStack.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Redirect to node edit form selected by the query params.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function nodeEditFormRedirect() {
    return $this->entityEditFormRedirect('node');
  }

  /**
   * Redirect to term edit form selected by the query params.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function termEditFormRedirect() {
    return $this->entityEditFormRedirect('taxonomy_term');
  }

  /**
   * Rend response qui redirige vers l'edit form de la dernière entité du type.
   *
   * @param string $entity_type
   *   Le type d'entité.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function entityEditFormRedirect($entity_type) {
    $entities = [];
    try {
      $entities = $this->getEntities($entity_type);
    }
    catch (\Exception $e) {
    }
    $entity = end($entities);
    if ($entity) {
      return $this->redirect('entity.' . $entity_type . '.edit_form', [$entity_type => $entity->id()]);
    }

    return $this->redirect('system.404');
  }

  /**
   * Retourne la liste d'entité en fonction des paramètres de query.
   *
   * @param string $entity_type
   *   LE type d'entité.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Les entités.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntities(string $entity_type) {
    // Permet de définir une propriété à un champ complexe avec séparateur ':'.
    $query = $this->currentRequest->query->all();
    $query = array_combine(array_map(function ($key) {
      return str_replace(':', '.', $key);
    }, array_keys($query)), $query);

    // Si une des conditions est tableau, alors on renvoie un type EntityQuery.
    foreach ($query as $queryElement) {
      if (is_array($queryElement)) {
        return $this->getEntitiesByEntityQuery($entity_type, $query);
      }
    }

    // Retourne une storage query.
    return $this->entityTypeManager
      ->getStorage($entity_type)
      ->loadByProperties($query);
  }

  /**
   * Retourne une liste d'entité en fonction des paramètres de query.
   *
   * @param string $entity_type
   *   Le type d'entité.
   * @param array $queryElements
   *   La query.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Les entités.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntitiesByEntityQuery(string $entity_type, array $queryElements) {
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
    foreach ($queryElements as $field => $queryElement) {
      if (is_array($queryElement)) {
        $query->condition($field, $queryElement['value'], $queryElement['type']);
      }
      else {
        $query->condition($field, $queryElement);
      }
    }

    return $this->entityTypeManager->getStorage($entity_type)
      ->loadMultiple($query->execute());
  }

}
