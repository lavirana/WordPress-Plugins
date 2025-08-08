<?php

declare(strict_types=1);

/**
 * Laravel Route Generator
 *
 * Usage:
 *   php laravel-route-generator.php --input route-spec.json [--stdout] [--out-web routes/web.php] [--out-api routes/api.php] [--overwrite] [--pretty]
 *
 * Input JSON schema (high-level):
 * {
 *   "groups": [
 *     {
 *       "type": "web" | "api",
 *       "prefix": "admin",                  // optional
 *       "name_prefix": "admin.",            // optional
 *       "middleware": ["auth", "verified"],// optional
 *       "domain": null,                      // optional
 *       "namespace": "App\\Http\\Controllers\\Admin", // optional, for clarity only
 *       "controllers": [
 *         {
 *           "model": "User",                  // optional; used to auto-derive uri/parameters when not provided
 *           "controller": "UserController",   // Required
 *           "fqcn": "App\\Http\\Controllers\\Admin\\UserController", // optional
 *           "uri": "users",                    // Optional; auto-derived from model if missing
 *           "resource": true,                    // If true, generate Route::resource
 *           "api_resource": false,               // If true, generate Route::apiResource
 *           "only": ["index", "show"],        // optional, for resource/apiResource
 *           "except": ["destroy"],             // optional, for resource/apiResource
 *           "parameters": {"users": "user"},  // optional ->parameters([...]); auto-derived from model if missing
 *           "names": {"index": "users.index"},// optional ->names([...]) or per-route name for explicit routes
 *           "shallow": false,                    // optional ->shallow()
 *           "methods": ["index", "show"],       // optional; generates explicit REST routes if not using resource/api_resource and routes[] not provided
 *           "routes": [                          // Optional explicit routes (used if resource/api_resource/methods not set)
 *             {"method": "get", "uri": "users/{user}", "action": "show", "name": "users.show", "middleware": ["can:view,user"]}
 *           ]
 *         }
 *       ]
 *     }
 *   ]
 * }
 */

// -------- CLI args parsing --------

function parseArgs(array $argv): array
{
    $args = [
        'input' => null,
        'stdout' => false,
        'out_web' => null,
        'out_api' => null,
        'overwrite' => false,
        'pretty' => false,
    ];

    foreach ($argv as $i => $arg) {
        if ($i === 0) {
            continue;
        }
        if ($arg === '--stdout') {
            $args['stdout'] = true;
        } elseif ($arg === '--overwrite') {
            $args['overwrite'] = true;
        } elseif ($arg === '--pretty') {
            $args['pretty'] = true;
        } elseif (str_starts_with($arg, '--input=')) {
            $args['input'] = substr($arg, 8);
        } elseif (str_starts_with($arg, '--out-web=')) {
            $args['out_web'] = substr($arg, 10);
        } elseif (str_starts_with($arg, '--out-api=')) {
            $args['out_api'] = substr($arg, 10);
        } elseif ($arg === '--help' || $arg === '-h') {
            fwrite(STDOUT, getHelpText());
            exit(0);
        }
    }

    if ($args['input'] === null) {
        fwrite(STDERR, "[Error] --input=path/to/route-spec.json is required. Use --help for details.\n");
        exit(1);
    }

    return $args;
}

function getHelpText(): string
{
    return <<<HELP
Laravel Route Generator

Usage:
  php laravel-route-generator.php --input route-spec.json [--stdout] [--out-web routes/web.php] [--out-api routes/api.php] [--overwrite] [--pretty]

Flags:
  --input=FILE     Path to JSON route spec
  --stdout         Print generated routes to stdout (both web and api sections)
  --out-web=FILE   Write web routes to file
  --out-api=FILE   Write api routes to file
  --overwrite      Allow overwriting existing output files
  --pretty         Pretty-print groups with blank lines between blocks
  --help, -h       Show this help

HELP;
}

// -------- Utilities --------

function readJsonFile(string $path): array
{
    if (!is_file($path)) {
        fwrite(STDERR, "[Error] Input file not found: {$path}\n");
        exit(1);
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        fwrite(STDERR, "[Error] Failed to read file: {$path}\n");
        exit(1);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fwrite(STDERR, "[Error] Invalid JSON in {$path}\n");
        exit(1);
    }
    return $data;
}

function indent(string $text, int $level = 1): string
{
    $pad = str_repeat('    ', $level);
    return preg_replace('/^/m', $pad, $text);
}

function exportPhpArray(array $arr): string
{
    // Export a PHP array in short syntax without line breaks for simple arrays
    $export = var_export($arr, true);
    // Convert array() to []
    $export = preg_replace(['/(\n\s*)/'], [' '], $export); // inline small arrays
    $export = str_replace(["array (", ")"], ['[', ']'], $export);
    $export = preg_replace('/=>\s+\n\s+\[/m', '=> [', $export);
    return $export;
}

function quote(string $value): string
{
    return "'" . str_replace("'", "\\'", $value) . "'";
}

// String helpers for model -> uri/param
function camelToWords(string $input): array
{
    $withSpaces = preg_replace('/(?<!^)([A-Z])/', ' $1', $input);
    $withSpaces = str_replace(['_', '-'], ' ', $withSpaces);
    $words = preg_split('/\s+/', trim((string)$withSpaces));
    return array_values(array_filter(array_map(fn($w) => strtolower($w), $words), fn($w) => $w !== ''));
}

function pluralize(string $word): string
{
    // naive pluralization
    if (preg_match('/(s|x|z|ch|sh)$/i', $word)) {
        return $word . 'es';
    }
    if (preg_match('/y$/i', $word) && !preg_match('/[aeiou]y$/i', $word)) {
        return substr($word, 0, -1) . 'ies';
    }
    return $word . 's';
}

function singularize(string $word): string
{
    if (preg_match('/ies$/i', $word)) {
        return substr($word, 0, -3) . 'y';
    }
    if (preg_match('/(s|x|z|ch|sh)es$/i', $word)) {
        return substr($word, 0, -2);
    }
    if (preg_match('/s$/i', $word)) {
        return substr($word, 0, -1);
    }
    return $word;
}

function modelToKebabPlural(string $model): string
{
    $words = camelToWords($model);
    if (count($words) === 0) {
        return '';
    }
    $last = array_pop($words);
    $lastPlural = pluralize($last);
    $words[] = $lastPlural;
    return implode('-', $words);
}

function modelToSnakeSingular(string $model): string
{
    $words = camelToWords($model);
    if (count($words) === 0) {
        return '';
    }
    // singularize last token
    $last = array_pop($words);
    $lastSingular = singularize($last);
    $words[] = $lastSingular;
    return implode('_', $words);
}

// -------- Generation Logic --------

function generateRoutes(array $spec, bool $pretty): array
{
    $webBlocks = [];
    $apiBlocks = [];

    $groups = $spec['groups'] ?? [];
    if (!is_array($groups)) {
        throw new InvalidArgumentException('Spec is missing top-level "groups" array.');
    }

    foreach ($groups as $groupIdx => $group) {
        $type = $group['type'] ?? 'web';
        if ($type !== 'web' && $type !== 'api') {
            throw new InvalidArgumentException("Group #{$groupIdx}: 'type' must be 'web' or 'api'.");
        }

        $prefix = $group['prefix'] ?? null;
        $namePrefix = $group['name_prefix'] ?? null;
        $middleware = $group['middleware'] ?? [];
        $domain = $group['domain'] ?? null;
        $controllers = $group['controllers'] ?? [];
        if (!is_array($controllers)) {
            throw new InvalidArgumentException("Group #{$groupIdx}: 'controllers' must be an array.");
        }
        $groupNamespace = $group['namespace'] ?? null;

        $lines = [];
        foreach ($controllers as $controllerIdx => $ctrl) {
            $controllerName = $ctrl['controller'] ?? null;
            if (!$controllerName) {
                throw new InvalidArgumentException("Group #{$groupIdx} Controller #{$controllerIdx}: 'controller' is required.");
            }
            $fqcn = $ctrl['fqcn'] ?? null; // optional for clarity in comments
            $uri = $ctrl['uri'] ?? null;
            $model = $ctrl['model'] ?? null;

            // Auto-derive uri/parameters from model if provided
            if ($model && !$uri) {
                $uri = modelToKebabPlural((string)$model);
            }

            // Resolve controller reference token for generated code
            $controllerClass = $controllerName;
            if ($fqcn && is_string($fqcn) && $fqcn !== '') {
                $controllerClass = $fqcn;
            } elseif (str_contains($controllerName, '\\')) {
                $controllerClass = ltrim($controllerName, '\\');
            } elseif ($groupNamespace) {
                $controllerClass = trim($groupNamespace, '\\') . '\\' . $controllerName;
            }
            $controllerToken = '\\' . ltrim($controllerClass, '\\') . '::class';

            $isResource = (bool)($ctrl['resource'] ?? false);
            $isApiResource = (bool)($ctrl['api_resource'] ?? false);

            $only = isset($ctrl['only']) ? array_values($ctrl['only']) : null;
            $except = isset($ctrl['except']) ? array_values($ctrl['except']) : null;
            $parameters = isset($ctrl['parameters']) ? $ctrl['parameters'] : null;
            $names = isset($ctrl['names']) ? $ctrl['names'] : null;
            $shallow = (bool)($ctrl['shallow'] ?? false);

            $explicitRoutes = $ctrl['routes'] ?? null;
            $methods = $ctrl['methods'] ?? null; // array of resource-like method names

            if (($isResource || $isApiResource) && !$uri) {
                throw new InvalidArgumentException("Group #{$groupIdx} Controller #{$controllerIdx}: 'uri' is required for resource/apiResource.");
            }

            if (($isResource || $isApiResource) && !$parameters && $model && $uri) {
                $parameters = [$uri => modelToSnakeSingular((string)$model)];
            }

            if ($isResource || $isApiResource) {
                $method = $isApiResource ? 'apiResource' : 'resource';
                $call = "Route::{$method}(" . quote((string)$uri) . ", {$controllerToken})";
                if ($only && $except) {
                    throw new InvalidArgumentException("Group #{$groupIdx} Controller #{$controllerIdx}: Use either 'only' or 'except', not both.");
                }
                if ($only) {
                    $call .= "->only(" . exportPhpArray($only) . ")";
                }
                if ($except) {
                    $call .= "->except(" . exportPhpArray($except) . ")";
                }
                if ($parameters && is_array($parameters) && count($parameters) > 0) {
                    $call .= "->parameters(" . exportPhpArray($parameters) . ")";
                }
                if ($names && is_array($names) && count($names) > 0) {
                    $call .= "->names(" . exportPhpArray($names) . ")";
                }
                if ($shallow) {
                    $call .= "->shallow()";
                }
                $call .= ";";

                $lines[] = $call;
            } elseif (is_array($explicitRoutes)) {
                foreach ($explicitRoutes as $routeIdx => $r) {
                    $httpMethod = strtolower((string)($r['method'] ?? 'get'));
                    $routeUri = (string)($r['uri'] ?? '');
                    $action = (string)($r['action'] ?? '');
                    if ($routeUri === '' || $action === '') {
                        throw new InvalidArgumentException("Group #{$groupIdx} Controller #{$controllerIdx} Route #{$routeIdx}: 'uri' and 'action' are required.");
                    }
                    $name = $r['name'] ?? null;
                    $routeMiddleware = $r['middleware'] ?? [];

                    $call = "Route::{$httpMethod}(" . quote($routeUri) . ", [{$controllerToken}, '" . addslashes($action) . "'])";
                    if ($name) {
                        $call .= "->name(" . quote((string)$name) . ")";
                    }
                    if ($routeMiddleware && is_array($routeMiddleware)) {
                        $call .= "->middleware(" . exportPhpArray(array_values($routeMiddleware)) . ")";
                    }
                    $call .= ";";
                    $lines[] = $call;
                }
            } elseif (is_array($methods) && $uri) {
                // Generate explicit RESTful routes from method list
                $param = $parameters && is_array($parameters) && isset($parameters[$uri])
                    ? (string)$parameters[$uri]
                    : ($model ? modelToSnakeSingular((string)$model) : 'id');
                $kebabBase = trim($uri, '/');
                foreach ($methods as $m) {
                    $m = (string)$m;
                    $mLower = strtolower($m);
                    switch ($mLower) {
                        case 'index':
                            $lines[] = "Route::get('{$kebabBase}', [{$controllerToken}, 'index'])->name('{$kebabBase}.index');";
                            break;
                        case 'create':
                            $lines[] = "Route::get('{$kebabBase}/create', [{$controllerToken}, 'create'])->name('{$kebabBase}.create');";
                            break;
                        case 'store':
                            $lines[] = "Route::post('{$kebabBase}', [{$controllerToken}, 'store'])->name('{$kebabBase}.store');";
                            break;
                        case 'show':
                            $lines[] = "Route::get('{$kebabBase}/{${param}}', [{$controllerToken}, 'show'])->name('{$kebabBase}.show');";
                            break;
                        case 'edit':
                            $lines[] = "Route::get('{$kebabBase}/{${param}}/edit', [{$controllerToken}, 'edit'])->name('{$kebabBase}.edit');";
                            break;
                        case 'update':
                            $lines[] = "Route::match(['put','patch'],'{$kebabBase}/{${param}}', [{$controllerToken}, 'update'])->name('{$kebabBase}.update');";
                            break;
                        case 'destroy':
                            $lines[] = "Route::delete('{$kebabBase}/{${param}}', [{$controllerToken}, 'destroy'])->name('{$kebabBase}.destroy');";
                            break;
                        default:
                            // custom method -> default to GET
                            $lines[] = "Route::get('{$kebabBase}/{$mLower}', [{$controllerToken}, '{$m}']);";
                    }
                }
            } else {
                // default single-action controller convention if only controller and uri provided
                if ($uri) {
                    $lines[] = "Route::get(" . quote((string)$uri) . ", {$controllerToken});";
                } else {
                    throw new InvalidArgumentException("Group #{$groupIdx} Controller #{$controllerIdx}: Provide 'resource'/'api_resource' or 'routes' or at least 'uri' for single-action.");
                }
            }
        }

        // Wrap with group if needed
        $groupPrefix = [];
        if ($middleware && is_array($middleware) && count($middleware) > 0) {
            $groupPrefix[] = "Route::middleware(" . exportPhpArray(array_values($middleware)) . ")";
        }
        if ($prefix) {
            $groupPrefix[] = "->prefix(" . quote((string)$prefix) . ")";
        }
        if ($namePrefix) {
            $groupPrefix[] = "->name(" . quote((string)$namePrefix) . ")";
        }
        if ($domain) {
            $groupPrefix[] = "->domain(" . quote((string)$domain) . ")";
        }

        if (count($groupPrefix) > 0) {
            $header = implode('', $groupPrefix) . "->group(function () {";
            $footer = "});";
            $block = $header . "\n" . indent(implode("\n", $lines)) . "\n" . $footer;
        } else {
            $block = implode("\n", $lines);
        }

        if ($pretty) {
            $block = "\n" . trim($block) . "\n"; // ensure spacing between groups
        }

        if ($type === 'api') {
            $apiBlocks[] = $block;
        } else {
            $webBlocks[] = $block;
        }
    }

    $webHeader = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

// Generated by laravel-route-generator.php
PHP;

    $apiHeader = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

// Generated by laravel-route-generator.php
PHP;

    $web = $webHeader . "\n\n" . trim(implode("\n\n", array_filter($webBlocks))) . "\n";
    $api = $apiHeader . "\n\n" . trim(implode("\n\n", array_filter($apiBlocks))) . "\n";

    return ['web' => $web, 'api' => $api];
}

function writeFileSafe(string $path, string $content, bool $overwrite): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: {$dir}");
        }
    }
    if (is_file($path) && !$overwrite) {
        throw new RuntimeException("Refusing to overwrite existing file without --overwrite: {$path}");
    }
    $ok = file_put_contents($path, $content);
    if ($ok === false) {
        throw new RuntimeException("Failed to write file: {$path}");
    }
}

// -------- Main --------

try {
    $args = parseArgs($argv);
    $spec = readJsonFile($args['input']);
    $generated = generateRoutes($spec, (bool)$args['pretty']);

    if ($args['stdout']) {
        fwrite(STDOUT, "/*** WEB ROUTES (routes/web.php) ***/\n\n");
        fwrite(STDOUT, $generated['web'] . "\n\n");
        fwrite(STDOUT, "/*** API ROUTES (routes/api.php) ***/\n\n");
        fwrite(STDOUT, $generated['api'] . "\n");
    }

    if ($args['out_web']) {
        writeFileSafe($args['out_web'], $generated['web'], (bool)$args['overwrite']);
        fwrite(STDOUT, "Wrote web routes to {$args['out_web']}\n");
    }
    if ($args['out_api']) {
        writeFileSafe($args['out_api'], $generated['api'], (bool)$args['overwrite']);
        fwrite(STDOUT, "Wrote api routes to {$args['out_api']}\n");
    }

    if (!$args['stdout'] && !$args['out_web'] && !$args['out_api']) {
        fwrite(STDOUT, "No output target specified. Use --stdout or --out-web/--out-api.\n");
        fwrite(STDOUT, getHelpText());
    }
} catch (Throwable $e) {
    fwrite(STDERR, "[Error] " . $e->getMessage() . "\n");
    exit(1);
}