<?php

/**
 * @file
 * Contains \Drupal\og\OgTestTrait
 */

namespace Drupal\og;

use Drupal\Component\Utility\Unicode;

trait OgTestTrait {

  /**
   * @var array
   *
   * List the type of schemas the test depend on.
   *
   * The structure of the array will be:
   *  - config: List of modules which own the config entity the test depend on.
   *  - entity: List all the content entity the test depends on.
   *  - modules: List all the non-entity tables the test depend on. Will be
   *    structured by: ['module' => ['table1', 'table2']]
   */
  public $schemas = [];

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The machine name of the group content node type.
   *
   * @var string
   */
  protected $groupContentBundle;

  /**
   * Installing the schemas the test depend on.
   *
   * @return $this
   */
  protected function installSchemas() {

    foreach ($this->schemas as $type => $schemas) {

      switch ($type) {
        case 'config':
          $this->installConfig($schemas);
          break;

        case 'entity':
          foreach ($schemas as $schema) {
            $this->installEntitySchema($schema);
          }
          break;

        case 'modules':
          foreach ($schemas as $module => $schema) {
            $this->installSchema($module, $schema);
          }
          break;
      }
    }

    return $this;
  }

  /**
   * Creating a group bundle.
   *
   * @param $entity_type_id
   *   The entity ID.
   *
   * @return $this
   */
  protected function createGroupBundle($entity_type_id) {
    $this->groupBundle = Unicode::strtolower($this->randomMachineName());
    Og::groupManager()->addGroup($entity_type_id, $this->groupBundle);
    return $this;
  }

}
