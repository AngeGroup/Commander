<?php
declare(strict_types=1);

namespace commander;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketAssembler;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketDisassembler;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\plugin\Plugin;
use commander\libs\muqsit\simplepackethandler\SimplePacketHandler;

class CommanderHandler {
    private Plugin $plugin;
    private static bool $isIntercepting = false;
    private static bool $isRegistered = false;

    public function __construct(Plugin $main) {
        $this->plugin = $main;
        $this->init();
    }

    public static function register(Plugin $plugin): void {
        if (self::$isRegistered) return;
        self::$isRegistered = true;
        new self($plugin);
    }

    private function init(): void {
        $interceptor = SimplePacketHandler::createInterceptor($this->plugin);
        $interceptor->interceptOutgoing(function(AvailableCommandsPacket $packet, NetworkSession $session): bool {
            if (self::$isIntercepting) return true;

            $player = $session->getPlayer();
            $commandMap = $this->plugin->getServer()->getCommandMap();

            $disassembled = AvailableCommandsPacketDisassembler::disassemble($packet);
            $commandDataList = $disassembled->commandData;

            foreach ($commandDataList as $commandData) {
                $cmd = $commandMap->getCommand($commandData->getName());
                if ($cmd instanceof CommandBase) {
                    $commandData->overloads = self::generateOverloads($player, $cmd);
                }
            }

            self::$isIntercepting = true;
            $session->sendDataPacket(AvailableCommandsPacketAssembler::assemble($commandDataList, [], EnumStore::getEnums()));
            self::$isIntercepting = false;

            return false;
        });
    }

    /**
     * @param CommandSender $sender
     * @param CommandBase $command
     * @return CommandOverload[]
     */
    private static function generateOverloads(CommandSender $sender, CommandBase $command): array {
        $overloads = [];
        foreach ($command->getSubCommands() as $label => $subCommand) {
            if (!$subCommand->testPermissionSilent($sender) || $subCommand->getName() !== $label) continue;

            $param = CommandParameter::enum(
                $label,
                new CommandHardEnum($label, [$label]),
                0
            );

            $overloadList = self::generateOverloadList($subCommand);
            if (!empty($overloadList)) {
                foreach ($overloadList as $overload) {
                    $overloads[] = new CommandOverload(false, [$param, ...$overload->getParameters()]);
                }
            } else {
                $overloads[] = new CommandOverload(false, [$param]);
            }
        }

        foreach (self::generateOverloadList($command) as $overload) {
            $overloads[] = $overload;
        }

        return $overloads;
    }

    /**
     * @param CommandBase $command
     * @return CommandOverload[]
     */
    private static function generateOverloadList(CommandBase $command): array {
        $input = $command->getParameters();
        if (empty($input)) return [];

        $combinations = [];
        $outputLength = array_product(array_map("count", $input));
        $indexes = [];
        foreach ($input as $k => $charList) $indexes[$k] = 0;

        do {
            $set = [];
            foreach ($indexes as $k => $index) {
                $param = clone $input[$k][$index]->getCommandParameter();
                $set[] = $param;
            }
            $combinations[] = new CommandOverload(false, $set);

            foreach ($indexes as $k => $v) {
                $indexes[$k]++;
                if ($indexes[$k] >= count($input[$k])) {
                    $indexes[$k] = 0;
                    continue;
                }
                break;
            }
        } while (count($combinations) < $outputLength);

        return $combinations;
    }
}