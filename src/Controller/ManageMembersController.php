<?php

namespace Drupal\islandora\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\Controller\NodeController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ManageMembersController extends EntityController {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManger;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EntityController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityFieldManager
   * The entity field manager.
   *
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    RendererInterface $renderer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('renderer')
    );
  }

  public function addToNodePage(NodeInterface $node) {
    return $this->generateTypeList(
      'node',
      'node_type',
      'node.add',
      'node.type_add',
	  $node,
	  'field_member_of'
    );
  }

  protected function generateTypeList($entity_type, $bundle_type, $entity_add_form, $bundle_add_form, NodeInterface $node, $field) {
    $type_definition = $this->entityTypeManager->getDefinition($bundle_type);

    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
      '#cache' => ['tags' => $type_definition->getListCacheTags()],
    ];

    $bundles = $this->entityTypeManager->getStorage($bundle_type)->loadMultiple();
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type);

    foreach (array_keys($bundles) as $bundle_id) {
      $bundle = $bundles[$bundle_id];

      // Skip bundles that don't have the specified field.
      $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle_id);
      if (!isset($fields[$field])) {
        continue;
      }

      $build['#bundles'][$bundle_id] = [
        'label' => $bundle->label(),
        'description' => $bundle->getDescription(),
        'add_link' => Link::createFromRoute(
          $bundle->label(),
          $entity_add_form,
          [$bundle_type => $bundle->id()],
          ['query' => ["edit[$field]" => $node->id()]]
        ),
      ];
    }

    // Filter out bundles the user can't create.
    foreach (array_keys($bundles) as $bundle_id) {
      $access = $access_control_handler->createAccess($bundle_id, NULL, [], TRUE);
      if (!$access->isAllowed()) {
        unset($build['#bundles'][$bundle_id]);
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Build the message shown when there are no bundles.
    $type_label = $type_definition->getLowercaseLabel();
    $link_text = $this->t('Add a new @entity_type.', ['@entity_type' => $type_label]);
    $build['#add_bundle_message'] = $this->t('There is no @entity_type yet. @add_link', [
      '@entity_type' => $type_label,
      '@add_link' => Link::createFromRoute($link_text, $bundle_add_form)->toString(),
    ]);

    return $build;
  }
}