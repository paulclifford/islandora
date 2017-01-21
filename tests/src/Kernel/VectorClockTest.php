<?php

namespace Drupal\Tests\islandora\Kernel;

use Drupal\islandora\Entity\FedoraResource;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests the basic behavior of a vector clock.
 *
 * @group islandora
 * @coversDefaultClass \Drupal\islandora\Entity\FedoraResource
 */
class VectorClockTest extends IslandoraKernelTestBase {

  use UserCreationTrait;

  /**
   * Fedora resource entity.
   *
   * @var \Drupal\islandora\FedoraResourceInterface
   */
  protected $entity;

  /**
   * User entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test user.
    $this->user = $this->createUser(['add fedora resource entities', 'edit fedora resource entities']);

    // Create a test entity.
    $this->entity = FedoraResource::create([
      "type" => "rdf_source",
      "uid" => $this->user->get('uid'),
      "name" => "Test Fixture",
      "langcode" => "und",
      "status" => 1,
    ]);
    $this->entity->save();

  }

  /**
   * Tests the basic behavior of the vector clock.
   *
   * @covers \Drupal\islandora\Entity\FedoraResource::getVclock
   */
  public function testVectorClock() {
    // Check the vclock is set to 0 when a new entity is created.
    $this->assertTrue($this->entity->getVclock() == 0, "Vector clock must be initialized to zero.");

    // Edit the entity.
    $this->entity->setName("Edited Test Fixture")->save();

    // Check the vclock is incremented when the entity is updated.
    $this->assertTrue($this->entity->getVclock() == 1, "Vector clock must be incremented on update.");
  }

}
