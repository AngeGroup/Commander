<?php
declare(strict_types=1);

namespace commander;

use Exception;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\Translatable;
use commander\parameters\Parameter;
use commander\parameters\TextParameter;

abstract class CommandBase extends Command {
    /** @var CommandBase[] */
    private array $subCommands = [];

    /** @var Parameter[][] */
    private array $parameters = [];

    /**
     * @param CommandBase[] $subCommands
     * @param string[]      $aliases
     * @throws Exception
     */
    public function __construct(string $name, string|Translatable $description, string $usageMessage, array $subCommands = [], array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        foreach ($this->getCommandParameters() as $position => $parameters) {
            $this->addParameter($position, $parameters);
        }

        foreach ($subCommands as $subCommand) {
            $this->registerSubCommand($subCommand);
        }
    }

    private function registerSubCommand(CommandBase $command) : void {
        $this->subCommands[$command->getName()] = $command;
    }

    /**
     * @return Parameter[]
     */
    abstract public function getCommandParameters() : array;

    /**
     * @param mixed[] $parameters
     */
    abstract protected function onRun(CommandSender $sender, array $parameters) : void;

    private function getTotalParameters(array $args) : int {
        $totalParameters = 0;

        foreach ($this->getCommandParameters() as $parameter) {
            $totalParameters++;
        }

        foreach ($args as $arg) {
            if (isset($this->subCommands[$arg])) {
                $subCommand = $this->subCommands[$arg];
                foreach ($subCommand->getCommandParameters() as $parameter) {
                    $totalParameters++;
                }
            }
        }

        return $totalParameters;
    }

    final public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        $commandParameters = $this->getCommandParameters();
        $totalParams       = $this->getTotalParameters($args);

        if (count($args) < $totalParams) {
            $isOptional = false;
            foreach ($commandParameters as $parameter) {
                if ($parameter->isOptional()) {
                    $isOptional = true;
                    break;
                }
            }
            if (!$isOptional) {
                $sender->sendMessage('§cUsage: ' . $this->getUsage());
                return;
            }
        }

        $passArgs = [];
        if (count($args) > 0) {
            $label = $args[0];
            if (isset($this->subCommands[$label])) {
                $cmd = $this->subCommands[$label];
                if (!$cmd->testPermissionSilent($sender)) {
                    $sender->sendMessage('§cErreur: Vous n\'avez pas la permission d\'utiliser cette commande.');
                    return;
                }
                $cmd->execute($sender, $commandLabel, array_slice($args, 1));
                return;
            }
            $passArgs = $this->parseArguments($args, $sender)['arguments'];
        } elseif ($this->hasRequiredArguments()) {
            $sender->sendMessage('§cUsage: ' . $this->getUsage());
            return;
        }

        try {
            $this->onRun($sender, $passArgs);
        } catch (InvalidCommandSyntaxException) {
            $sender->sendMessage('§cUsage: ' . $this->getUsage());
        }
    }

    /**
     * @return CommandBase[]
     */
    public function getSubCommands() : array {
        return $this->subCommands;
    }

    /**
     * @throws Exception
     */
    private function addParameter(int $position, Parameter $parameters) : void {
        foreach ($this->parameters[$position - 1] ?? [] as $arg) {
            if ($arg instanceof TextParameter) {
                throw new Exception("No other arguments can be registered after a TextParameter");
            }
            if ($arg->isOptional() && !$parameters->isOptional()) {
                throw new Exception("You cannot register a required argument after an optional argument");
            }
        }
        $this->parameters[$position][] = $parameters;
    }

    final public function getParameters() : array {
        return $this->parameters;
    }

    public function hasRequiredArguments() : bool {
        foreach ($this->getCommandParameters() as $parameter) {
            if (!$parameter->isOptional()) {
                return true;
            }
        }
        return false;
    }

    public function hasParameters() : bool {
        return !empty($this->parameters);
    }

    private function parseArguments(array $rawArgs, CommandSender $sender) : array {
        $return = ["arguments" => []];
        if (!$this->hasParameters() && count($rawArgs) > 0) {
            return $return;
        }
        $offset = 0;
        foreach ($this->parameters as $pos => $possibleParameter) {
            usort($possibleParameter, function (Parameter $a, Parameter $b) : int {
                if ($a->getSpanLength() === PHP_INT_MAX) {
                    return 1;
                }
                return -1;
            });
            $parsed = false;
            foreach ($possibleParameter as $argument) {
                if ($argument instanceof TextParameter) {
                    $arg = trim(implode(" ", array_slice($rawArgs, $offset)));
                } else {
                    $arg = trim(implode(" ", array_slice($rawArgs, $offset, ($len = $argument->getSpanLength()))));
                }
                if ($arg !== "" && $argument->canParse($arg, $sender)) {
                    $k = $argument->getName();

                    $result = (clone $argument)->parse($arg, $sender);

                    if (isset($return["arguments"][$k]) && !is_array($return["arguments"][$k])) {
                        $old = $return["arguments"][$k];
                        unset($return["arguments"][$k]);
                        $return["arguments"][$k]   = [$old];
                        $return["arguments"][$k][] = $result;
                    } else {
                        $return["arguments"][$k] = $result;
                    }

                    if (!($argument instanceof TextParameter)) {
                        $offset += $len;
                    } else {
                        $offset = count($rawArgs);
                    }
                    $parsed = true;
                    break;
                }
            }
            if (!$parsed) {
                return $return;
            }
        }
        return $return;
    }
}