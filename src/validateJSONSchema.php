<?php
/**
 * @author Andreas Wahlen
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once __DIR__.'/functions.php';

use Opis\JsonSchema\Validator;
use Opis\JsonSchema\Errors\ErrorFormatter;
use CLIParser\CLIParser;

if(PHP_SAPI !== 'cli' || !isset($_SERVER['argv'])){
  exit(1);          // exit if not run via CLI
}

echo 'JSON Schema Validator @git-version@ by Andreas Wahlen'.PHP_EOL.PHP_EOL;

if(!class_exists(\Opis\JsonSchema\Validator::class)){
  echo "\033[31mFATAL: Opis\JsonSchema\Validator not found, is it required in your composer.json?\033[0m".PHP_EOL.PHP_EOL;
  exit(10);
}

$cliArgs = new CLIParser($_SERVER['argv']);
$cliArgs->setAllowedOptions([]);
$cliArgs->setAllowedFlags([]);
$cliArgs->setStrictMode(true);
if(!$cliArgs->parse() || empty($cliArgs->getCommands())){
  echo 'usage: php '.basename(__FILE__).' schema_file1.json schema_folder2 [...]'.PHP_EOL;
  exit(2);
}

$startTime = microtime(true);
// collect schema files to validate
try {
  $files = collectSchemaFiles($cliArgs->getCommands());
} catch(InvalidArgumentException $ex){
  echo "\033[31m".$ex->getMessage()."\033[0m".PHP_EOL;
  exit(20);
}

$cwd = getcwd();
$validator = new Validator();
$validator->resolver()->registerPrefix('https://json-schema.org/', dirname(__DIR__).'/resource');
$validator->resolver()->registerPrefix('http://json-schema.org/', dirname(__DIR__).'/resource');
$errors = 0;
foreach($files as $index => $file){
  $countChars = strlen(strval(count($files)));
  $formattedFilename = str_pad(shortenString(str_starts_with($file, $cwd) ? substr($file, strlen($cwd)) : $file, 79), 80);
  echo '['.str_pad(strval($index+1), $countChars, ' ', STR_PAD_LEFT).'/'.count($files).'] '.
      $formattedFilename;
  try {
    /** @var scalar|object $json */
    $json = json_decode(file_get_contents($file), false, flags: JSON_THROW_ON_ERROR);
  } catch(JsonException $ex){
    echo "[\033[31mSYNTAX ERROR\033[0m]".PHP_EOL;
    $errors++;
    continue;
  }
  
  try {
    $result = $validator->validate($json, $json->{'$schema'} ?? 'https://json-schema.org/draft/2020-12/schema');
    if(!$result->isValid()){
      $errorMessage = json_encode(((new ErrorFormatter())->format($result->error())),
          JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
      $errorMessage = str_replace('%24', '$', $errorMessage);
      $errorMessage = indent($errorMessage, $countChars * 2 + 4);
      echo "[\033[31mSCHEMA ERROR\033[0m]".PHP_EOL.$errorMessage.PHP_EOL;
      $errors++;
    } else {
      echo "[\033[32mOK\033[0m]".PHP_EOL;
    }
  } catch(\ErrorException $ex){
    if(mb_strpos($ex->getMessage(), 'preg_match(): Compilation failed: ') === 0){
      echo "[\033[31mSYNTAX ERROR\033[0m]".PHP_EOL.indent($ex->getMessage(), $countChars * 2 + 4).PHP_EOL;
      $errors++;
    } else {
      throw $ex;
    }
  }
}
echo 'consumed '.number_format(microtime(true) - $startTime, 3).' s of processing time and '.
    (memory_get_peak_usage(true) / 1024 / 1024).' MiB of memory'.PHP_EOL.PHP_EOL;
echo 'JSON Schemas: '.count($files).', errors: '.$errors.PHP_EOL;
if($errors > 0){
  exit(30);
}
