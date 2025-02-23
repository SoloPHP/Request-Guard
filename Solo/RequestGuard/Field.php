<?php

namespace Solo\RequestGuard;

class Field
{
    public function __construct(
        public readonly string  $name,
        public readonly mixed   $default = null,
        public readonly ?string $rules = null,
        public readonly mixed   $preprocessor = null,
        public readonly mixed   $postprocessor = null,
        public readonly ?string $inputName = null
    )
    {
    }

    public static function for(string $name): self
    {
        return new self($name);
    }

    public function mapFrom(string $inputName): self
    {
        return new self(
            name: $this->name,
            default: $this->default,
            rules: $this->rules,
            preprocessor: $this->preprocessor,
            postprocessor: $this->postprocessor,
            inputName: $inputName
        );
    }

    public function default(mixed $value): self
    {
        return new self(
            name: $this->name,
            default: $value,
            rules: $this->rules,
            preprocessor: $this->preprocessor,
            postprocessor: $this->postprocessor,
            inputName: $this->inputName
        );
    }

    public function validate(string $rules): self
    {
        return new self(
            name: $this->name,
            default: $this->default,
            rules: $rules,
            preprocessor: $this->preprocessor,
            postprocessor: $this->postprocessor,
            inputName: $this->inputName
        );
    }

    public function preprocess(callable $handler): self
    {
        return new self(
            name: $this->name,
            default: $this->default,
            rules: $this->rules,
            preprocessor: $handler,
            postprocessor: $this->postprocessor,
            inputName: $this->inputName
        );
    }

    public function postprocess(callable $handler): self
    {
        return new self(
            name: $this->name,
            default: $this->default,
            rules: $this->rules,
            preprocessor: $this->preprocessor,
            postprocessor: $handler,
            inputName: $this->inputName
        );
    }

    public function processPre(mixed $value): mixed
    {
        return $this->preprocessor && is_callable($this->preprocessor)
            ? ($this->preprocessor)($value)
            : $value;
    }

    public function processPost(mixed $value): mixed
    {
        return $this->postprocessor && is_callable($this->postprocessor)
            ? ($this->postprocessor)($value)
            : $value;
    }
}