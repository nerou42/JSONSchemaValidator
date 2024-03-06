# JSON Schema Validator

[![License](http://poser.pugx.org/nerou/json-schema-validator/license)](https://packagist.org/packages/nerou/json-schema-validator)
[![Version](http://poser.pugx.org/nerou/json-schema-validator/version)](https://packagist.org/packages/nerou/json-schema-validator)
[![Psalm Type Coverage](https://shepherd.dev/github/nerou42/JSONSchemaValidator/coverage.svg)](https://packagist.org/packages/nerou/json-schema-validator)

Validates some [JSON Schema](https://json-schema.org/) files against the JSON Schema specification.

## Installation

`composer require [--dev] nerou/json-schema-validator`

## Usage

Just pass some JSON schema files or folders containing those like so:

```shell
./vendor/bin/json-schema-validator schema-file1.json schema-file2.json schema-folder [...]
```

## License

This library is licensed under the MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
