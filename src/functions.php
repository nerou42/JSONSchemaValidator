<?php
/**
 * @author Andreas Wahlen
 */

declare(strict_types=1);

/**
 * @param string[] $fileList
 * @return string[]
 * @psalm-return list<string>
 * @throws InvalidArgumentException if a file is not readable
 */
function collectSchemaFiles(array $fileList): array {
  $files = [];
  foreach($fileList as $filename){
    if(!is_readable($filename)){
      throw new InvalidArgumentException($filename.' is not readable');
    }
    if(is_file($filename) && str_ends_with($filename, '.json') !== false){
      $files[] = $filename;
    } else if(is_dir($filename)){
      $files = array_merge($files, listSchemasInFolder($filename));
    }
  }
  return $files;
}

/**
 * @return string[]
 * @psalm-return list<string>
 */
function listSchemasInFolder(string $folder): array {
  $folder = str_ends_with($folder, '/') ? substr($folder, 0, strlen($folder) - 1) : $folder;
  $res = [];
  foreach(scandir($folder) as $filename){
    if(is_file($folder.'/'.$filename) && is_readable($folder.'/'.$filename) && str_ends_with($filename, '.json') !== false){
      $res[] = $folder.'/'.$filename;
    } else if(is_dir($folder.'/'.$filename) && is_readable($folder.'/'.$filename) && $filename !== '.' && $filename !== '..'){
      $res = array_merge($res, listSchemasInFolder($folder.'/'.$filename));
    }
  }
  return $res;
}

/**
 * @psalm-pure
 */
function indent(string $lines, int $indentation = 4): string {
  $spaces = str_pad(' ', $indentation);
  return $spaces.implode("\n".$spaces, explode("\n", $lines));
}

/**
 * @psalm-pure
 */
function shortenString(string $str, int $targetLength): string {
  if(strlen($str) <= $targetLength){
    return $str;
  }
  return substr($str, 0, intval(floor($targetLength / 2)) - 2).'...'.substr($str, strlen($str) - intval(ceil($targetLength / 2)) + 1);
}
