<?php
/**
 * @file
 * Contains \Drupal\file_entity\Controller\FileController.
 */

namespace Drupal\file_entity\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FileController
 */
class FileController extends ControllerBase {

  /**
   * Upload
   */
  public function FileAddUpload() {

  }

  /**
   * File
   */
  public function FileAddUploadFile() {

  }

  /**
   * Archive
   */
  public function FileAddUploadArchive() {

  }

  /**
   * Usage
   *
   * @param $file
   */
  public function FileUsage($file) {
    //@TODO: File Usage here.
  }

  /**
   * Returns a HTTP response for a file being downloaded.
   *
   * @param FileInterface $file
   *   The file to download, as an entity.
   *
   * @return Response
   *   The file to download, as a response.
   */
  public function download(FileInterface $file) {
    // Ensure there is a valid token to download this file.
    if (!$this->config('file_entity.settings')->get('allow_insecure_download')) {
      if (!isset($_GET['token']) || $_GET['token'] !== file_entity_get_download_token($file)) {
        return new Response(t('Access to file @url denied', array('@url' => $file->getFileUri())), 403);
      }
    }

    $headers = array(
      'Content-Type' => Unicode::mimeHeaderEncode($file->getMimeType()),
      'Content-Disposition' => 'attachment; filename="' . Unicode::mimeHeaderEncode(drupal_basename($file->getFileUri())) . '"',
      'Content-Length' => $file->getSize(),
      'Content-Transfer-Encoding' => 'binary',
      'Pragma' => 'no-cache',
      'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
      'Expires' => '0',
    );

    // Let other modules alter the download headers.
    \Drupal::moduleHandler()->alter('file_download_headers', $headers, $file);

    // Let other modules know the file is being downloaded.
    \Drupal::moduleHandler()->invokeAll('file_transfer', array($file->getFileUri(), $headers));

    try {
      return new BinaryFileResponse($file->getFileUri(), 200, $headers);
    }
    catch (FileNotFoundException $e) {
      return new Response(t('File @uri not found', array('@uri' =>$file->getFileUri())), 404);
    }
  }
}
