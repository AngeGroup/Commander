<?php
declare(strict_types=1);

namespace commander\parameters;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

abstract class EnumParameter extends Parameter {

    protected array $values = [];

    public function __construct(string $name, bool $isOptional = false) {
        parent::__construct($name, $isOptional);
        $this->commandParameter = CommandParameter::enum($name, new CommandHardEnum('', $this->getEnumValues()), 0, $isOptional);
    }

    public function getNetworkType() : int {
        return -1;
    }

    public function canParse(string $argument, CommandSender $sender): bool {
        return (bool) preg_match(
            "/^(".implode("|", array_map('\\strtolower', $this->getEnumValues())).")$/iu",
            $argument
        );
    }

    public function addValue(string $string, bool|float|int|string $value): void {
        $this->values[strtolower($string)] = $value;
    }

    public function getValue(string $string): null|bool|float|int|string {
        return $this->values[strtolower($string)];
    }

    /**
     * @return string[]|bool[]|int[]|float[]
     */
    public function getEnumValues(): array {
        return array_keys($this->values);
    }

    public function getValues(): array {
        return $this->values;
    }

}