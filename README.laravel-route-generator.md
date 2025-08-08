## Laravel Route Generator (Standalone)

Generate Laravel route definitions (web and api) from a compact JSON spec describing your models/controllers/methods.

### Files
- `laravel-route-generator.php`: PHP CLI script
- `route-spec.example.json`: sample input

### Requirements
- PHP 8.1+

### Usage
Run and print to stdout:

```bash
php /workspace/laravel-route-generator.php --input /workspace/route-spec.example.json --stdout --pretty
```

Write directly to a Laravel project's route files:

```bash
php /workspace/laravel-route-generator.php \
  --input /workspace/route-spec.example.json \
  --out-web /path/to/laravel/routes/web.php \
  --out-api /path/to/laravel/routes/api.php \
  --overwrite --pretty
```

### JSON Spec (overview)
- **groups**: array of web/api groups
  - **type**: `web` or `api`
  - **prefix**: optional URI prefix
  - **name_prefix**: optional name prefix
  - **middleware**: array of middleware
  - **domain**: optional domain constraint
  - **namespace**: optional group namespace to resolve controller names
  - **controllers**: array of controller blocks
    - **model**: optional Model name, used to auto-derive `uri` and `parameters` (e.g., `User` -> `users`, parameter `user`)
    - **controller**: controller class (string). Use FQCN or rely on `namespace`.
    - **fqcn**: optional explicit FQCN override
    - **uri**: base URI or route URI; auto-derived from `model` if omitted
    - One of:
      - **resource**: true (generates `Route::resource`)
      - **api_resource**: true (generates `Route::apiResource`)
      - **routes**: explicit routes array
      - **methods**: array of RESTful method names to generate explicit routes (`index|create|store|show|edit|update|destroy`)
    - Optional (resources):
      - **only** / **except**
      - **parameters**: map of resource => parameter (auto from `model` if omitted)
      - **names**: map of method => name
      - **shallow**: boolean
    - Optional (explicit routes):
      - item: `{ method, uri, action, name?, middleware? }`

### Minimal example using `model` and `methods`

```json
{
  "groups": [
    {
      "type": "api",
      "prefix": "v1",
      "namespace": "App\\Http\\Controllers\\Api\\V1",
      "middleware": ["api"],
      "controllers": [
        {
          "model": "Article",
          "controller": "ArticleController",
          "methods": ["index", "show", "store", "update", "destroy"]
        }
      ]
    }
  ]
}
```

This generates API routes equivalent to `Route::apiResource('articles', ArticleController::class)` using explicit endpoints, with `{article}` parameter.

### Notes
- Generated files include `use Illuminate\Support\Facades\Route;`.
- Controller references are emitted as fully-qualified class references, using `fqcn`, group `namespace`, or the given name if already FQCN.
- To avoid overwriting existing files, the script requires `--overwrite` to write to a path that already exists.