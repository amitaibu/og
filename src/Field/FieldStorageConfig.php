<?php

namespace Drupal\og\Field;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Overrides the FieldConfigStorage definition class to set custom storage.
 */
class FieldStorageConfig implements FieldStorageConfigInterface {

  /**
   * The decorated field storage config instance.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $decorated;

  /**
   * Constructs a FieldStorageConfig decorator object.
   */
  public function __construct(FieldStorageConfigInterface $field_storage_config) {
    $this->decorated = $field_storage_config;
  }

  /**
   * Magic __call method to delegate all methods to decorated class.
   *
   * @param $name
   * @param $arguments
   */
  public function __call($name, $arguments) {
    return call_user_func_array([$this->decorated, $name], $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function hasCustomStorage() {
    return TRUE;
  }

  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->decorated->access($operation, $account, $return_as_object);
  }

  public function getCacheContexts() {
    return $this->decorated->getCacheContexts();
  }

  public function getCacheTags() {
    return $this->decorated->getCacheTags();
  }

  public function getCacheMaxAge() {
    return $this->decorated->getCacheMaxAge();
  }

  public function enable() {
    return $this->decorated->enable();
  }

  public function disable() {
    return $this->decorated->disable();
  }

  public function setStatus($status) {
    return $this->decorated->setStatus($status);
  }

  public function setSyncing($status) {
    return $this->decorated->setSyncing($status);
  }

  public function status() {
    return $this->decorated->status();
  }

  public function isSyncing() {
    return $this->decorated->isSyncing();
  }

  public function isUninstalling() {
    return $this->decorated->isUninstalling();
  }

  public function get($property_name) {
    return $this->decorated->get($property_name);
  }

  public function set($property_name, $value) {
    return $this->decorated->set($property_name, $value);
  }

  public function calculateDependencies() {
    return $this->decorated->calculateDependencies();
  }

  public function onDependencyRemoval(array $dependencies) {
    return $this->decorated->onDependencyRemoval($dependencies);
  }

  public function getDependencies() {
    return $this->decorated->getDependencies();
  }

  public function isInstallable() {
    return $this->decorated->isInstallable();
  }

  public function trustData() {
    return $this->decorated->trustData();
  }

  public function hasTrustedData() {
    return $this->decorated->hasTrustedData();
  }

  public function uuid() {
    return $this->decorated->uuid();
  }

  public function id() {
    return $this->decorated->id();
  }

  public function language() {
    return $this->decorated->language();
  }

  public function isNew() {
    return $this->decorated->isNew();
  }

  public function enforceIsNew($value = TRUE) {
    return $this->decorated->enforceIsNew($value);
  }

  public function getEntityTypeId() {
    return $this->decorated->getEntityTypeId();
  }

  public function bundle() {
    return $this->decorated->bundle();
  }

  public function label() {
    return $this->decorated->label();
  }

  public function urlInfo($rel = 'canonical', array $options = array()) {
    return $this->decorated->urlInfo($rel, $options);
  }

  public function url($rel = 'canonical', $options = array()) {
    return $this->decorated->url($rel, $options);
  }

  public function link($text = NULL, $rel = 'canonical', array $options = []) {
    return $this->decorated->link($text, $rel, $options);
  }

  public function hasLinkTemplate($key) {
    return $this->decorated->hasLinkTemplate($key);
  }

  public function uriRelationships() {
    return $this->decorated->uriRelationships();
  }

  public static function load($id) {

  }

  public static function loadMultiple(array $ids = NULL) {

  }

  public static function create(array $values = array()) {

  }

  public function save() {
    return $this->decorated->save();
  }

  public function delete() {
    return $this->decorated->delete();
  }

  public function preSave(EntityStorageInterface $storage) {
    return $this->decorated->preSave($storage);
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    return $this->decorated->postSave($storage, $update);
  }

  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    // TODO: Implement preCreate() method.
  }

  public function postCreate(EntityStorageInterface $storage) {
    return $this->decorated->postCreate($storage);
  }

  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    // TODO: Implement preDelete() method.
  }

  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // TODO: Implement postDelete() method.
  }

  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    // TODO: Implement postLoad() method.
  }

  public function createDuplicate() {
    return $this->decorated->createDuplicate();
  }

  public function getEntityType() {
    return $this->decorated->getEntityType();
  }

  public function referencedEntities() {
    return $this->decorated->referencedEntities();
  }

  public function getOriginalId() {
    return $this->decorated->getOriginalId();
  }

  public function getCacheTagsToInvalidate() {
    return $this->decorated->getCacheTagsToInvalidate();
  }

  public function setOriginalId($id) {
    return $this->decorated->setOriginalId($id);
  }

  public function toArray() {
    return $this->decorated->toArray();
  }

  public function getTypedData() {
    return $this->decorated->getTypedData();
  }

  public function getConfigDependencyKey() {
    return $this->decorated->getConfigDependencyKey();
  }

  public function getConfigDependencyName() {
    return $this->decorated->getConfigDependencyName();
  }

  public function getConfigTarget() {
    return $this->decorated->getConfigTarget();
  }

  public function getType() {
    return $this->decorated->getType();
  }

  public function getTypeProvider() {
    return $this->decorated->getTypeProvider();
  }

  public function getBundles() {
    return $this->decorated->getBundles();
  }

  public function isDeleted() {
    return $this->decorated->isDeleted();
  }

  public function isDeletable() {
    return $this->decorated->isDeletable();
  }

  public function isLocked() {
    return $this->decorated->isLocked();
  }

  public function setLocked($locked) {
    return $this->decorated->setLocked($locked);
  }

  public function setCardinality($cardinality) {
    return $this->decorated->setCardinality($cardinality);
  }

  public function setSetting($setting_name, $value) {
    return $this->decorated->setSetting($setting_name, $value);
  }

  public function setSettings(array $settings) {
    return $this->decorated->setSettings($settings);
  }

  public function setTranslatable($translatable) {
    return $this->decorated->setTranslatable($translatable);
  }

  public function getIndexes() {
    return $this->decorated->getIndexes();
  }

  public function setIndexes(array $indexes) {
    return $this->decorated->setIndexes($indexes);
  }

  public function getName() {
    return $this->decorated->getName();
  }

  public function getSettings() {
    return $this->decorated->getSettings();
  }

  public function getSetting($setting_name) {
    return $this->decorated->getSetting($setting_name);
  }

  public function isTranslatable() {
    return $this->decorated->isTranslatable();
  }

  public function isRevisionable() {
    return $this->decorated->isRevisionable();
  }

  public function isQueryable() {
    return $this->decorated->isQueryable();
  }

  public function getLabel() {
    return $this->decorated->getLabel();
  }

  public function getDescription() {
    return $this->decorated->getDescription();
  }

  public function getOptionsProvider($property_name, FieldableEntityInterface $entity) {
    return $this->decorated->getOptionsProvider($property_name, $entity);
  }

  public function isMultiple() {
    return $this->decorated->isMultiple();
  }

  public function getCardinality() {
    return $this->decorated->getCardinality();
  }

  public function getPropertyDefinition($name) {
    return $this->decorated->getPropertyDefinition($name);
  }

  public function getPropertyDefinitions() {
    return $this->decorated->getPropertyDefinitions();
  }

  public function getPropertyNames() {
    return $this->decorated->getPropertyNames();
  }

  public function getMainPropertyName() {
    return $this->decorated->getMainPropertyName();
  }

  public function getTargetEntityTypeId() {
    return $this->decorated->getTargetEntityTypeId();
  }

  public function getSchema() {
    return $this->decorated->getSchema();
  }

  public function getColumns() {
    return $this->decorated->getColumns();
  }

  public function getConstraints() {
    return $this->decorated->getConstraints();
  }

  public function getConstraint($constraint_name) {
    return $this->decorated->getConstraint($constraint_name);
  }

  public function getProvider() {
    return $this->decorated->getProvider();
  }

  public function isBaseField() {
    return $this->decorated->isBaseField();
  }

  public function getUniqueStorageIdentifier() {
    return $this->decorated->getUniqueStorageIdentifier();
  }

  public function addCacheContexts(array $cache_contexts) {
    return $this->decorated->addCacheContexts($cache_contexts);
  }

  public function addCacheTags(array $cache_tags) {
    return $this->decorated->addCacheTags($cache_tags);
  }

  public function mergeCacheMaxAge($max_age) {
    return $this->decorated->mergeCacheMaxAge($max_age);
  }

  public function addCacheableDependency($other_object) {
    return $this->decorated->addCacheableDependency($other_object);
  }

  public function setThirdPartySetting($module, $key, $value) {
    return $this->decorated->setThirdPartySetting($module, $key, $value);
  }

  public function getThirdPartySetting($module, $key, $default = NULL) {
    return $this->decorated->getThirdPartySetting($module, $key, $default);
  }

  public function getThirdPartySettings($module) {
    return $this->decorated->getThirdPartySettings($module);
  }

  public function unsetThirdPartySetting($module, $key) {
    return $this->decorated->unsetThirdPartySetting($module, $key);
  }

  public function getThirdPartyProviders() {
    return $this->decorated->getThirdPartyProviders();
  }

}
