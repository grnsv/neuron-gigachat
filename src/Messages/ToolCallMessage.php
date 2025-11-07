<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat\Messages;

use NeuronAI\Tools\ToolInterface;

/**
 * @method static static make(array<int, mixed>|string|int|float|null $content, ToolInterface[] $tools)
 */
class ToolCallMessage extends \NeuronAI\Chat\Messages\ToolCallMessage
{
    public function getTool(): ToolInterface
    {
        return $this->tools[0];
    }

    public function jsonSerialize(): array
    {
        $tool = $this->getTool();

        return [
            ...parent::jsonSerialize(),
            'function_call' => [
                'name' => $tool->getName(),
                'arguments' => $tool->getInputs(),
            ],
            'functions_state_id' => $tool->getCallId(),
        ];
    }
}
