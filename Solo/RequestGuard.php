<?php declare(strict_types=1);

namespace Solo;

use Solo\RequestGuard\Exceptions\AuthorizationException;
use Solo\RequestGuard\Exceptions\ValidationException;
use Solo\RequestGuard\Exceptions\UncleanQueryException;
use Psr\Http\Message\ServerRequestInterface;
use Solo\Validator\ValidatorInterface;
use Solo\RequestGuard\Field;

abstract class RequestGuard
{
    public function __construct(
        protected ValidatorInterface $validator
    )
    {
    }

    /**
     * Main request processing method.
     * Performs GET parameter cleaning, authorization, preparation, validation, and post-processing.
     *
     * @throws AuthorizationException If the request is not authorized
     * @throws ValidationException If the data fails validation
     * @throws UncleanQueryException If GET parameters contain unclean values
     */
    public function handle(ServerRequestInterface $request): array
    {
        $data = $this->getRequestData($request);

        if ($request->getMethod() === 'GET') {
            $this->ensureCleanGetRequest($request, $data);
        }

        if (!$this->authorize()) {
            throw new AuthorizationException('Unauthorized request.');
        }

        $prepared = $this->prepareData($data);
        $this->validate($prepared);
        return $this->applyPostprocess($prepared);
    }

    /**
     * Validates and checks GET request for clean query parameters.
     *
     * @throws UncleanQueryException If the query contains unnecessary or default parameters
     */
    private function ensureCleanGetRequest(ServerRequestInterface $request, array $data): void
    {
        $allowed = $this->filterAllowed($data);
        $cleaned = $this->removeDefaults($allowed);

        if ($cleaned != $data) {
            $uri = $request->getUri()->withQuery(http_build_query($cleaned));
            throw new UncleanQueryException($cleaned, (string)$uri);
        }
    }

    /**
     * Extracts raw data array from request (GET/POST).
     */
    public function getRequestData(ServerRequestInterface $request): array
    {
        return match ($request->getMethod()) {
            'GET' => $request->getQueryParams(),
            default => array_merge(
                (array)($request->getParsedBody() ?? []),
                $request->getQueryParams()
            ),
        };
    }

    /**
     * Removes fields with values that match default values.
     */
    public function removeDefaults(array $data): array
    {
        $defaults = $this->getDefaults();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $defaults) && (string)$value === (string)$defaults[$key]) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Gathers default values for fields.
     */
    public function getDefaults(): array
    {
        $defaults = [];
        foreach ($this->fields() as $field) {
            if ($field->default !== null) {
                $defaults[$field->name] = $field->default;
            }
        }
        return $defaults;
    }

    /**
     * Filters input data to keep only allowed fields.
     */
    private function filterAllowed(array $data): array
    {
        $allowedKeys = [];
        foreach ($this->fields() as $field) {
            $allowedKeys[$field->name] = true;
        }

        return array_intersect_key($data, $allowedKeys);
    }

    /**
     * Transforms raw data into prepared data by applying field pre-processors.
     */
    private function prepareData(array $data): array
    {
        $result = [];
        foreach ($this->fields() as $field) {
            $rawValue = $this->extractNestedValue($data, $field->inputName, $field->default);
            $result[$field->name] = $field->processPre($rawValue);
        }
        return $result;
    }

    /**
     * Validates prepared data against rules.
     *
     * @throws ValidationException If validation fails
     */
    private function validate(array $data): void
    {
        $rules = [];
        foreach ($this->fields() as $field) {
            if (!empty($field->rules)) {
                $rules[$field->name] = $field->rules;
            }
        }

        $errors = $this->validator->validate($data, $rules, $this->messages());
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Applies field post-processors to validated data.
     */
    private function applyPostprocess(array $data): array
    {
        foreach ($this->fields() as $field) {
            if (array_key_exists($field->name, $data)) {
                $data[$field->name] = $field->processPost($data[$field->name]);
            }
        }
        return $data;
    }

    /**
     * Extracts a value from a nested array using dot notation path.
     *
     * @param mixed $default The default value if path not found
     * @return mixed The extracted value or default
     */
    private function extractNestedValue(array $data, string $path, mixed $default = null): mixed
    {
        $keys = explode('.', $path);
        $current = $data;
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }
        return $current;
    }

    /**
     * Returns array of field definitions.
     *
     * @return array<Field> Array of field objects
     */
    abstract protected function fields(): array;

    /**
     * Returns array of custom validation messages.
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Determines if the request is authorized.
     */
    protected function authorize(): bool
    {
        return true;
    }
}