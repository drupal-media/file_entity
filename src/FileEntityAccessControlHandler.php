<?php

/**
 * @file
 * Contains \Drupal\file_entity\FileEntityAccessControlHandler.
 */

namespace Drupal\file_entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessControlHandler;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Defines the access control handler for the file entity type.
 */
class FileEntityAccessControlHandler extends FileAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $result = AccessResult::allowedIfHasPermission($account, 'bypass file access')
      ->orIf(parent::access($entity, $operation, $langcode, $account, TRUE));
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $result = AccessResult::allowedIfHasPermission($account, 'bypass file access')
      ->orIf(parent::createAccess($entity_bundle, $account, $context, TRUE));
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create files');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var FileEntity $entity */

    $is_owner = $entity->getOwnerId() === $account->id();

    if ($operation == 'view') {
      $wrapper = file_entity_get_stream_wrapper(file_uri_scheme($entity->getFileUri()));
      if (!empty($wrapper['private'])) {
        return AccessResult::allowedIfHasPermission($account, 'view private files')
          ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $is_owner)
            ->andIf(AccessResult::allowedIfHasPermission($account, 'view own private files')));
      }
      elseif ($entity->isPermanent()) {
        return AccessResult::allowedIfHasPermission($account, 'view files')
          ->orIf(AccessResult::allowedIf($is_owner)
            ->andIf(AccessResult::allowedIfHasPermission($account, 'view own files')));
      }
    }

    // User can perform these operations if they have the "any" permission or if
    // they own it and have the "own" permission.
    if (in_array($operation, array('download', 'edit', 'delete'))) {
      $type = $entity->get('type')->target_id;
      return AccessResult::allowedIfHasPermission($account, "$operation any $type files")
        ->orIf(AccessResult::allowedIf($is_owner)
          ->andIf(AccessResult::allowedIfHasPermission($account, "$operation own $type files")));
    }
  }
}
