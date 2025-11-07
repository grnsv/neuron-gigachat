<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat\Tools;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class ReturnStructuredOutput extends Tool
{
    public function __construct(
        protected readonly string $class,
        protected readonly array $response_format,
    ) {
        parent::__construct(
            'return_structured_output',
            'Функция для возврата структурированного ответа.',
        );
    }

    /**
     * Properties are the input arguments of the __invoke method.
     */
    protected function properties(): array
    {
        $properties = [];
        $required = $this->response_format['required'] ?? [];
        foreach ($this->response_format['properties'] as $name => $property) {
            $properties[] = new ToolProperty(
                name: $name,
                type: PropertyType::fromSchema($property['type']),
                description: $property['description'] ?? null,
                required: in_array($name, $required),
                enum: $property['enum'] ?? [],
            );
        }

        return $properties;
    }

    /**
     * Implementing the tool logic
     */
    public function __invoke(...$input): object
    {
        return new $this->class(...$input);
    }
}
