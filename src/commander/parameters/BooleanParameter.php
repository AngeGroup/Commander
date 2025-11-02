<?php
declare(strict_types=1);

namespace commander\parameters;

use pocketmine\command\CommandSender;

class BooleanParameter extends EnumParameter {

    public function __construct(string $name, bool $isOptional = false) {
        parent::__construct($name, $isOptional);
        $this->addValue('on', true);
        $this->addValue('off', false);
    }

    public function parse(string $argument, CommandSender $sender) : string|int|bool|float {
        $value = $this->getValue($argument);
        if ($value === null) {
            throw new \InvalidArgumentException('Invalid boolean value');
        }
        return $value;
    }

}