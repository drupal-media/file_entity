<?php

/**
 * @file
 * Contains \Drupal\file_entity\FileEntityAccessController.
 */

namespace Drupal\file_entity;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessController;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Defines the access controller for the file entity type.
 */
class FileEntityAccessController extends FileAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    $account = $this->prepareUser($account);
    return $account->hasPermission('bypass file access') ||
      parent::access($entity, $operation, $langcode, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array()) {
    $account = $this->prepareUser($account);
    return $account->hasPermission('bypass file access') ||
      parent::createAccess($entity_bundle, $account, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('create files');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var FileEntity $entity */

    $is_owner = $entity->getOwnerId() === $account->id();
    debug($entity->getOwnerId());
    debug($account->id());
    debug($account->hasPermission('view files'), 'view files');
    debug($is_owner, 'owner');
    debug($operation);

    if ($operation == 'view') {
      $wrapper = file_entity_get_stream_wrapper(file_uri_scheme($entity->getFileUri()));
      return
        // For private files, users can view private files if the
        // user has the 'view private files' permission.
        !empty($wrapper['private']) && $account->hasPermission('view private files') ||
        // For private files, users can view their own private files if the user
        // is not anonymous, and has the 'view own private files' permission.
        !empty($wrapper['private']) && !$account->isAnonymous() && $is_owner && $account->hasPermission('view own private files') ||
        // For non-private files, allow to see if user owns the file.
        $entity->isPermanent() && $is_owner && $account->hasPermission('view own files') ||
        // For non-private files, users can view if they have the 'view files'
        // permission.
        $entity->isPermanent() && $account->hasPermission('view files');
    }

    // User can perform these operations if they have the "any" permission or if
    // they own it and have the "own" permission.
    $operation_permission_map = array(
      'download' => 'download',
      'update' => 'edit',
      'delete' => 'delete',
    );
    if ($permission = @$operation_permission_map[$operation]) {
      $type = $entity->get('type')->target_id;
      return $account->hasPermission("$permission any $type files") ||
        ($is_owner && $account->hasPermission("$permission own $type files"));
    }
  }
}
