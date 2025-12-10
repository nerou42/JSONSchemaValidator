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

echo 'JSON Schema Validator @git-version@ by nerou GmbH'.PHP_EOL.PHP_EOL;

$cliArgs = new CLIParser($_SERVER['argv'], 'schema_file1.json schema_folder2 [...]');
$cliArgs->setAllowedOptions([]);
$cliArgs->setAllowedFlags([]);
$cliArgs->setStrictMode(true);
if(!$cliArgs->parse() || empty($cliArgs->getCommands())){
  $cliArgs->printUsage();
  exit(2);
}

$startTime = microtime(true);
// collect schema files to validate
try {
  $files = collectSchemaFiles($cliArgs->getCommands());
} catch(\InvalidArgumentException $ex){
  echo "\e[31m".$ex->getMessage()."\e[0m".PHP_EOL;
  exit(20);
}

$cwd = getcwd();
if($cwd === false){
  throw new \UnexpectedValueException('unable to get current working directory');
}
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
    $fileContent = file_get_contents($file);
    if($fileContent === false){
      throw new \UnexpectedValueException('file '.$file.' could not be read');
    }
    /**
     * @var scalar|object $json
     */
    $json = json_decode($fileContent, flags: JSON_THROW_ON_ERROR);
  } catch(JsonException $ex){
    echo "[\e[31mSYNTAX ERROR\e[0m]".PHP_EOL;
    $errors++;
    continue;
  }
  
  try {
    /**
     * @psalm-suppress PossiblyInvalidPropertyFetch
     * @var string $schema
     */
    $schema = $json->{'$schema'} ?? 'https://json-schema.org/draft/2020-12/schema';
    $result = $validator->validate($json, $schema);
    if(!$result->isValid()){
      $errorMessage = json_encode(((new ErrorFormatter())->format($result->error())),
          JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
      $errorMessage = str_replace('%24', '$', $errorMessage);
      $errorMessage = indent($errorMessage, $countChars * 2 + 4);
      echo "[\e[31mSCHEMA ERROR\e[0m]".PHP_EOL.$errorMessage.PHP_EOL;
      $errors++;
    } else {
      echo "[\e[32mOK\e[0m]".PHP_EOL;
    }
  } catch(\ErrorException $ex){
    if(mb_strpos($ex->getMessage(), 'preg_match(): Compilation failed: ') === 0){
      echo "[\e[31mSYNTAX ERROR\e[0m]".PHP_EOL.indent($ex->getMessage(), $countChars * 2 + 4).PHP_EOL;
      $errors++;
    } else {
      throw $ex;
    }
  }
}
echo sprintf('Time: %.3s s, Memory: %.2f MiB'.PHP_EOL.PHP_EOL,
    microtime(true) - $startTime,
    ((float) memory_get_peak_usage(true)) / 1024. / 1024.
);
echo 'JSON Schemas: '.count($files).', errors: '.$errors.PHP_EOL;
if($errors > 0){
  exit(30);
}
