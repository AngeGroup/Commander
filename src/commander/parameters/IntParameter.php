<?php
declare(strict_types=1);

namespace commander\parameters;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

class IntParameter extends Parameter {

    public function __construct(string $name, bool $isOptional = false) {
        parent::__construct($name, $isOptional);
    }

    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_INT;
    }

    public function canParse(string $argument, CommandSender $sender): bool {
        return (bool)preg_match('/^\d+$/', $argument);
    }

    public function parse(string $argument, CommandSender $sender): int {
        return (int) $argument;
    }
}