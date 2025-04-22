# Solo Request Guard 🛡️
[![Version](https://img.shields.io/badge/version-1.2.0-blue.svg)](https://github.com/solophp/request-guard)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

**Robust request validation & authorization layer for HTTP inputs with type-safe guards**
---
## ✨ Features
- **Type-safe request validation**
- **Field mapping** from custom input names/nested structures
- **Built-in authorization checks**
- **Multi-stage processing pipeline** (`preprocess` → `validate` → `postprocess`)
- **Custom error messages**
- **PSR-7 compatible**
- **Smart request data merging** (POST body > GET params)
- **GET query cleaning with exception support**
- **Immutable field definitions**
---
## 🔗 Dependencies
- [PSR-7 HTTP Message Interface](https://github.com/php-fig/http-message) (`psr/http-message` ^2.0)
- [Solo Validator](https://github.com/solophp/validator) (`solophp/validator` ^2.1)
---

## 📥 Installation

```bash
composer require solophp/request-guard
```

---

## 🚀 Quick Start

### Define a Request Guard
```php
namespace App\Requests;

use Solo\RequestGuard;
use Solo\RequestGuard\Field;

class CreateArticleRequest extends RequestGuard 
{
    protected function fields(): array 
    {
        return [
            Field::for('author_email')
                ->mapFrom('meta.author.email')
                ->validate('required|email'),
                
            Field::for('title')
                ->validate('required|string|max:100')
                ->preprocess('trim'),
            
            Field::for('status')
                ->default('draft')
                ->validate('string|in:draft,published')
                ->postprocess(fn($v) => strtoupper($v))
        ];
    }

    protected function authorize(): bool 
    {
        return $this->user()->can('create', Article::class);
    }
}
```

---

### Handle in Controller
```php
namespace App\Controllers;

use App\Requests\CreateArticleRequest;
use Solo\RequestGuard\Exceptions\{ValidationException, AuthorizationException};

class ArticleController 
{
    public function store(ServerRequestInterface $request) 
    {
        try {
            $data = (new CreateArticleRequest(new Validator()))->handle($request);
            Article::create($data);
            return response()->json(['success' => true], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->getErrors()], 422);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
```

---

## ⚙️ Field Configuration

| Method                | Required? | Description                                      |  
|-----------------------|-----------|--------------------------------------------------|  
| `Field::for(string)`  | **Yes**   | Starts field definition                          |  
| `mapFrom(string)`     | No        | Map input from custom name/nested path           |
| `default(mixed)`      | No        | Fallback value if field is missing               |  
| `validate(string)`    | No        | Validation rules (e.g., `required|string|max:5`) |  
| `preprocess(callable)`| No        | Transform raw input **before validation**        |  
| `postprocess(callable)`| No       | Modify value **after validation**                |  

### Processing Pipeline
1. **Map Input** - Resolve value using `mapFrom` path  
2. **Preprocess** - Clean/transform raw input  
3. **Validate** - Check against rules  
4. **Postprocess** - Final value adjustments  

### Example
```php
Field::for('tags')
    ->mapFrom('raw_csv')
    ->preprocess(fn($v) => explode(',', $v))
    ->validate('array|max:5')
    ->postprocess(fn($v) => array_unique($v));
```

---

## 🔄 Request Data Handling

- **Nested Structures**: Use dot notation (`mapFrom('contacts.user_name')`)
- **GET**: Query parameters only  
- **POST/PUT/PATCH**: Merged body + query (body priority)  
- **Files**: Via `$request->getUploadedFiles()`  

---

## ⚡ Error Handling

### ValidationException (HTTP 422)
  ```php
catch (ValidationException $e) {
    return ['errors' => $e->getErrors()]; // Format: ['field' => ['Error 1']]
}
  ```

### AuthorizationException (HTTP 403)
  ```php
catch (AuthorizationException $e) {
    return ['message' => $e->getMessage()]; // "Unauthorized request"
}
  ```
### UncleanQueryException (HTTP 400 or custom)
```php
catch (UncleanQueryException $e) {
    return redirect($e->getRedirectUri());
}
```
---

## 🚦 Custom Messages
```php
protected function messages(): array {
    return [
        'author_email.required' => 'Author email required',
        'status.in' => 'Invalid status: :value'
    ];
}
```

---

## 🛠️ Testing
```php
public function test_nested_mapping() {
        $data = $request->handle(
        $this->createRequest('POST', '/', [
            'meta' => ['author' => ['email' => 'test@example.com']]
            ])
        );
    $this->assertEquals('test@example.com', $data['author_email']);
}
```
---
## 📚 Public API
| Method                  | Description                                                              |
|--------------------------|--------------------------------------------------------------------------|
| `handle(ServerRequestInterface $request): array` | Main entry point: cleans, authorizes, validates, postprocesses |
| `getRequestData(ServerRequestInterface $request): array` | Merges GET/POST params, returns raw input                    |
| `removeDefaults(array $data): array`             | Strips default values from the given array                   |
| `getDefaults(): array`                           | Returns all non-null default field values                   |
---
## ⚙️ Requirements
- PHP 8.1+
---

## 📄 License
MIT - See [LICENSE](LICENSE) for details.