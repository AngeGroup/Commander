<?php
declare(strict_types=1);

namespace commander\parameters;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

abstract class Parameter {
    private string $name;
    private bool $isOptional;
    public CommandParameter $commandParameter;

    public function __construct(
        string $name,
        bool $isOptional = false
    ) {
        $this->name = $name;
        $this->isOptional = $isOptional;
        $this->commandParameter = CommandParameter::standard($name,
            $this->getNetworkType(),
            0,
            $isOptional
        );
    }

    public function getName(): string {
        return $this->name;
    }

    public function isOptional(): bool {
        return $this->isOptional;
    }

    public function getCommandParameter(): CommandParameter {
        return $this->commandParameter;
    }

    public function getSpanLength(): int {
        return 1;
    }

    abstract public function getNetworkType(): int;

    abstract public function parse(string $argument, CommandSender $sender): mixed;

    abstract public function canParse(string $argument, CommandSender $sender): bool;
}
