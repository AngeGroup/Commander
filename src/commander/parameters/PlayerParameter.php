<?php
declare(strict_types=1);

namespace commander\parameters;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\Player;
use pocketmine\Server;

class PlayerParameter extends Parameter {

    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_TARGET;
    }

    public function canParse(string $argument, CommandSender $sender): bool {
        return true;
    }

    public function parse(string $argument, CommandSender $sender): Player|string {
        if(($player = Server::getInstance()->getPlayerExact($argument)) !== null) {
            return $player;
        }else{
            return $argument;
        }
    }
}