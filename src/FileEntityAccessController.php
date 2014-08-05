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

    if ($operation == 'view') {
      $wrapper = file_entity_get_stream_wrapper(file_uri_scheme($entity->getFileUri()));
      if (!empty($wrapper['private'])) {
        return
          $account->hasPermission('view private files') ||
          ($account->isAuthenticated() && $is_owner && $account->hasPermission('view own private files'));
      }
      elseif ($entity->isPermanent()) {
        return
          $account->hasPermission('view files') ||
          ($is_owner && $account->hasPermission('view own files'));
      }
    }

    // User can perform these operations if they have the "any" permission or if
    // they own it and have the "own" permission.
    if (in_array($operation, array('download', 'edit', 'delete'))) {
      $type = $entity->get('type')->target_id;
      return $account->hasPermission("$operation any $type files") ||
        ($is_owner && $account->hasPermission("$operation own $type files"));
    }
  }
}
