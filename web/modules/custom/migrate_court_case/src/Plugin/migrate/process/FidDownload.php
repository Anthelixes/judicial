<?php

namespace Drupal\migrate_court_case\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Download;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Downloads a file from a HTTP(S) remote location into the local file system and return the fid.
 *
 * @MigrateProcessPlugin(
 *   id = "fid_download"
 * )
 */
class FidDownload extends Download {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a uri of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    list($source, $destination) = $value;

    // Modify the destination filename if necessary.
    $final_destination = $this->fileSystem->getDestinationFilename($destination, $this->configuration['file_exists']);

    // Reuse if file exists.
    if (!$final_destination) {
      return $destination;
    }

    // Try opening the file first, to avoid calling prepareDirectory()
    // unnecessarily. We're suppressing fopen() errors because we want to try
    // to prepare the directory before we give up and fail.
    $destination_stream = @fopen($final_destination, 'w');
    if (!$destination_stream) {
      // If fopen didn't work, make sure there's a writable directory in place.
      $dir = $this->fileSystem->dirname($final_destination);
      if (!$this->fileSystem->prepareDirectory($dir, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        throw new MigrateException("Could not create or write to directory '$dir'");
      }
      // Let's try that fopen again.
      $destination_stream = @fopen($final_destination, 'w');
      if (!$destination_stream) {
        throw new MigrateException("Could not write to file '$final_destination'");
      }
    }

    // Stream the request body directly to the final destination stream.
    $this->configuration['guzzle_options']['sink'] = $destination_stream;

    try {
      // Make the request. Guzzle throws an exception for anything but 200.
      $this->httpClient->get($source, $this->configuration['guzzle_options']);
    }
    catch (\Exception $e) {
      throw new MigrateException("{$e->getMessage()} ($source)");
    }

    $file = File::create([
      'uid' => 1,
      'filename' => basename($destination),
      'uri' => $destination,
      'filemime' => $this->configuration['filemime'],
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();
    return $file->id();
  }

}
