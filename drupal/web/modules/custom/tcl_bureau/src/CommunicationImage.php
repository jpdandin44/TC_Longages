<?php

declare(strict_types=1);

namespace Drupal\tcl_bureau;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/** Decode and re-encode uploads: never serve the original file or its metadata. */
final class CommunicationImage {
  public static function prepare(UploadedFile $file): string {
    if (!$file->isValid() || $file->getSize() > 8 * 1024 * 1024) {
      throw new \InvalidArgumentException('Choisissez une image de 8 Mo maximum.');
    }
    $bytes = file_get_contents($file->getPathname());
    $info = @getimagesizefromstring($bytes);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], TRUE) || $info[0] * $info[1] > 20000000) {
      throw new \InvalidArgumentException('Image JPEG, PNG ou WebP requise, de 20 millions de pixels maximum.');
    }
    if (!function_exists('imagecreatefromstring')) {
      throw new \InvalidArgumentException('La préparation des images est indisponible sur ce serveur.');
    }
    $source = @imagecreatefromstring($bytes);
    if (!$source) {
      throw new \InvalidArgumentException('Cette image ne peut pas être lue.');
    }
    $ratio = min(1, 1600 / max($info[0], $info[1]));
    $width = max(1, (int) round($info[0] * $ratio));
    $height = max(1, (int) round($info[1] * $ratio));
    $target = imagecreatetruecolor($width, $height);
    imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
    imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
    $result = '';
    foreach ([85, 70, 55, 40] as $quality) {
      ob_start();
      imagejpeg($target, NULL, $quality);
      $result = ob_get_clean();
      if (strlen($result) <= 512000) {
        break;
      }
    }
    imagedestroy($source);
    imagedestroy($target);
    if (strlen($result) > 512000) {
      throw new \InvalidArgumentException('L’image préparée dépasse 500 ko. Choisissez une image plus légère.');
    }
    return $result;
  }
}
