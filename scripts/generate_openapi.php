<?php
require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;

$openapi = \OpenApi\scan([__DIR__ . '/../src/OpenApiSpec.php']);
header('Content-Type: application/x-yaml');
file_put_contents(__DIR__ . '/../docs/openapi.yaml', $openapi->toYaml());
echo "OpenAPI spec generated at docs/openapi.yaml\n";
