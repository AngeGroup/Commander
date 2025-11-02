<?php
declare(strict_types=1);

namespace commander;

use Exception;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use pocketmine\network\mcpe\protocol\UpdateSoftEnumPacket;
use pocketmine\Server;

class EnumStore {

    /** @var CommandSoftEnum[] */
    private static array $enums = [];

    public static function getEnum(string $name): ?CommandSoftEnum {
        return self::$enums[$name] ?? null;
    }

    /**
     * @return CommandSoftEnum[]
     */
    public static function getEnums(): array {
        return self::$enums;
    }

    public static function addEnum(CommandSoftEnum $enum): void {
        self::$enums[$enum->getName()] = $enum;
        self::broadcastEnum($enum, UpdateSoftEnumPacket::TYPE_ADD);
    }

    public static function updateEnum(string $enumName, array $values): void {
        if (!isset(self::$enums[$enumName])) {
            throw new Exception("Unknown enum named " . $enumName);
        }
        $enum = self::$enums[$enumName] = new CommandSoftEnum($enumName, $values);
        self::broadcastEnum($enum, UpdateSoftEnumPacket::TYPE_SET);
    }

    public static function removeEnum(string $enumName): void {
        if (!isset(self::$enums[$enumName])) {
            throw new Exception("Unknown enum named " . $enumName);
        }
        $enum = self::$enums[$enumName];
        unset(self::$enums[$enumName]);
        self::broadcastEnum($enum, UpdateSoftEnumPacket::TYPE_REMOVE);
    }

    private static function broadcastEnum(CommandSoftEnum $enum, int $type): void {
        $packet = new UpdateSoftEnumPacket();
        $packet->enumName = $enum->getName();
        $packet->values = $enum->getValues();
        $packet->type = $type;
        self::broadcastPacket($packet);
    }

    private static function broadcastPacket(ClientboundPacket $packet): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $player->getNetworkSession()->sendDataPacket($packet);
        }
    }
}