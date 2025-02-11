# Solo Request Guard 🛡️

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/solophp/request-guard)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

**Robust request validation & authorization layer for HTTP inputs with type-safe guards**

---

## ✨ Features

- **Type-safe request validation**
- **Built-in authorization checks**
- **Multi-stage processing pipeline** (`preprocess` → `validate` → `postprocess`)
- **Custom error messages**
- **PSR-7 compatible**
- **Smart request data merging** (POST body > GET params)
- **Immutable field definitions**

## 🔗 Dependencies

- [PSR-7 HTTP Message Interface](https://github.com/php-fig/http-message) (`psr/http-message` ^2.0)
- [Solo Validator](https://github.com/solophp/validator) (`solophp/validator` ^2.0)

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
            Field::for('title')
                ->validate('required|string|max:100')
                ->preprocess('trim'),
            
            Field::for('content')
                ->validate('required|string|min:500'),
                
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
            return response()->json([
                'errors' => $e->getErrors()
            ], 422);
            
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }
    }
}
```

---

## ⚙️ Field Configuration

### Available Methods
| Method                | Required? | Description                                      |  
|-----------------------|-----------|--------------------------------------------------|  
| `Field::for(string)`  | **Yes**   | Starts field definition                          |  
| `default(mixed)`      | No        | Fallback value if field is missing               |  
| `validate(string)`    | No        | Validation rules (e.g., `required|string|max:5`) |  
| `preprocess(callable)`| No        | Transform raw input **before validation**        |  
| `postprocess(callable)`| No       | Modify value **after validation**                |  


### Processing Pipeline
1. **Preprocess** - Clean/transform raw input  
2. **Validate** - Check against rules  
3. **Postprocess** - Final value adjustments  

### Field Processing Example
```php
// Trim input and deduplicate tags
Field::for('tags')
    ->default([])
    ->validate('array')
    ->preprocess(fn($v) => array_map('trim', (array)$v))
    ->postprocess(fn($v) => array_unique($v));

// Parse date and format output
Field::for('publish_date')
    ->validate('required|date')
    ->preprocess(fn($v) => new DateTime($v))
    ->postprocess(fn($v) => $v->format('Y-m-d'));
```

---

## 🔄 Request Data Handling

- **GET requests**: Only query parameters  
- **POST/PUT/PATCH**: Merged body + query (body has priority)  
- **File uploads**: Accessed via `$request->getUploadedFiles()`  

---

## ⚡ Error Handling

### ValidationException (HTTP 422)
  ```php
try {
    $data = $requestGuard->handle($serverRequest);
} catch (ValidationException $e) {
    return [
        'errors' => $e->getErrors() // Format: ['field' => ['Error 1', 'Error 2']]
    ];
}
  ```

### AuthorizationException (HTTP 403)
  ```php
catch (AuthorizationException $e) {
    return [
        'message' => $e->getMessage() // "Unauthorized request"
    ];
}
  ```

---

## 🚦 Custom Validation Messages
```php
protected function messages(): array 
{
    return [
        'title.required' => 'Please provide an article title',
        'content.min' => 'Content too short (min: :min chars)',
        'status.in' => 'Invalid status: :value'
    ];
}
```

---

## 🛠️ Testing
```php
class ArticleRequestTest extends TestCase 
{
    public function test_publish_date_processing()
    {
        $request = new ArticleRequest(new Validator());

        $data = $request->handle(
            $this->createRequest('POST', '/articles', [
                'publish_date' => '2023-10-01'
            ])
        );
        
        $this->assertInstanceOf(DateTime::class, $data['publish_date']);
        $this->assertEquals('2023-10-01', $data['publish_date']->format('Y-m-d'));
    }
}
```

---

## ⚙️ Requirements

- PHP 8.2+

---


## 📄 License
MIT - See [LICENSE](LICENSE) for details.