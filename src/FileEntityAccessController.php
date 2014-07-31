<?php

/**
 * @file
 * Contains \Drupal\file_entity\FileEntityAccessController.
 */

namespace Drupal\file_entity;

use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access controller for the file entity type.
 */
class FileEntityAccessController extends FileAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass file access')) {
      return TRUE;
    }
    if (!$account->hasPermission('access content')) {
      return FALSE;
    }
    return parent::access($entity, $operation, $langcode, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array()) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass file access')) {
      return TRUE;
    }
    if (!$account->hasPermission('access content')) {
      return FALSE;
    }

    return parent::createAccess($entity_bundle, $account, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('create ' . $entity_bundle . ' content');
  }

}
