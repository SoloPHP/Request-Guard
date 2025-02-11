<?php

namespace Solo;

use Solo\RequestGuard\Exceptions\AuthorizationException;
use Solo\RequestGuard\Exceptions\ValidationException;
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

    public function handle(ServerRequestInterface $request): array
    {
        if (!$this->authorize()) {
            throw new AuthorizationException('Unauthorized request.');
        }

        $requestData = $this->getRequestData($request);
        $preprocessed = $this->prepareData($requestData);
        $this->validate($preprocessed);
        $postprocessed = $this->applyPostprocess($preprocessed);

        return $postprocessed;
    }

    /**
     * @return array<Field>
     */
    abstract protected function fields(): array;

    protected function messages(): array { return []; }

    protected function authorize(): bool { return true; }

    private function getRequestData(ServerRequestInterface $request): array
    {
        return match ($request->getMethod()) {
            'GET' => $request->getQueryParams(),
            default => [
                ...(array)($request->getParsedBody() ?? []),
                ...$request->getQueryParams()
            ]
        };
    }

    private function prepareData(array $requestData): array
    {
        $result = [];
        foreach ($this->fields() as $field) {
            $value = $requestData[$field->name] ?? $field->default;
            $result[$field->name] = $field->processPre($value);
        }
        return $result;
    }

    private function validate(array $data): void
    {
        $rules = [];
        foreach ($this->fields() as $field) {
            if ($field->rules) {
                $rules[$field->name] = $field->rules;
            }
        }

        $errors = $this->validator->validate($data, $rules, $this->messages());
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function applyPostprocess(array $data): array
    {
        foreach ($this->fields() as $field) {
            if (isset($data[$field->name])) {
                $data[$field->name] = $field->processPost($data[$field->name]);
            }
        }
        return $data;
    }
}