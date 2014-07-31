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

    // Fall back to default behaviors on view.
    if ($operation == 'view') {
      $wrapper = file_entity_get_stream_wrapper(file_uri_scheme($entity->getFileUri()));

      if (!empty($wrapper['private'])) {
        // For private files, users can view private files if the
        // user has the 'view private files' permission.
        if ($account->hasPermission('view private files')) {
          return TRUE;
        }

        // For private files, users can view their own private files if the user
        // is not anonymous, and has the 'view own private files' permission.
        if (!$account->isAnonymous() && $entity->getOwnerId() == $account->id() && $account->hasPermission('view own private files')) {
          return TRUE;
        }
      }
      elseif ($entity->isPermanent() && $entity->getOwnerId() == $account->id() && $account->hasPermission('view own files')) {
        // For non-private files, allow to see if user owns the file.
        return TRUE;
      }
      elseif ($entity->isPermanent() && $account->hasPermission('view files')) {
        // For non-private files, users can view if they have the 'view files'
        // permission.
        return TRUE;
      }
    }
  }
}
