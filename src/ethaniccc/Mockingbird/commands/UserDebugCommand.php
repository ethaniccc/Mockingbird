<?php

namespace ethaniccc\Mockingbird\commands;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\DebugWriteTask;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\tasks\PacketLogWriteTask;
use ethaniccc\Mockingbird\user\UserManager;
use ethaniccc\Mockingbird\utils\MouseRecorder;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Villager;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacketTest;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\DataPacket;

class UserDebugCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    public function __construct(Mockingbird $plugin){
        $this->plugin = $plugin;
        parent::__construct('mbdebug', 'Get the anti-cheat debug logs of a user and a specified detection', '/mbdebug <player> <detection_name>', []);
        $this->setPermission('mockingbird.debug');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($this->testPermission($sender)){
            $selectedUser = $args[0] ?? null;
            $selectedCheat = $args[1] ?? null;
            if($selectedUser === null || $selectedCheat === null){
                $sender->sendMessage($this->getUsage());
            } else {
                $user = UserManager::getInstance()->getUserByName($selectedUser);
                if($selectedUser === '--toggle-debug' && $sender instanceof Player){
                    $user = UserManager::getInstance()->get($sender);
                    $user->debugChannel = $selectedCheat === 'off' ? null : strtolower($selectedCheat);
                    $selectedCheat === 'off' ? $sender->sendMessage('Debug information has been disabled.') : $sender->sendMessage("Debug information for $selectedCheat has been enabled.");
                    return;
                } /* elseif($selectedUser === '--spawn-dummy' && $sender instanceof Player){
                    $nbt = Entity::createBaseNBT($sender->asVector3());
                    $dummy = new Villager($sender->getLevelNonNull(), $nbt);
                    $dummy->setMaxHealth(10000);
                    $dummy->setHealth(10000);
                    $dummy->spawnTo($sender);
                    return;
                } */ elseif($selectedUser === '--packet-log'){
                    $u = UserManager::getInstance()->getUserByName($selectedCheat);
                    if($u !== null){
                        $u->isPacketLogged = !$u->isPacketLogged;
                        $u->isPacketLogged ? $sender->sendMessage($this->plugin->getPrefix() . ' ' . TextFormat::GREEN . $u->player->getName() . "'s packets are now being logged.") : $sender->sendMessage($this->plugin->getPrefix() . ' ' . TextFormat::RED . $u->player->getName() . "'s are no longer being logged. The packet log is now available.");
                        if(!$u->isPacketLogged && count($u->packetLog) > 0){
                            $task = new PacketLogWriteTask($this->plugin->getDataFolder() . 'packet_logs/' . $u->player->getName(), $u->packetLog);
                            Server::getInstance()->getAsyncPool()->submitTask($task);
                            $u->packetLog = [];
                        }
                    } else {
                        $sender->sendMessage($this->plugin->getPrefix() . TextFormat::RED . ' This user was not found.');
                    }
                    return;
                } /* elseif($selectedUser === '--motion-test' && $sender instanceof Player && ($entity = $sender->getLevel()->getEntity((int) $selectedCheat)) !== null){
                    // $entity->setMotion(new Vector3(0.4, 0.4, 0.4));
                    $pk = new SetActorMotionPacket();
                    $pk->motion = new Vector3(1, 2, 1);
                    $pk->entityRuntimeId = (int) $selectedCheat;
                    $sender->dataPacket($pk);
                    return;
                } */ elseif($selectedUser === '--record-mouse' && $sender instanceof Player){
                    // mbdebug --record-mouse coEthaniccc 30 2
                    $u = UserManager::getInstance()->get($sender);
                    $p = $sender->getServer()->getPlayer($selectedCheat);
                    if($p !== null && $u !== null && ($tu = UserManager::getInstance()->get($p)) !== null){
                        $tu->mouseRecorder = new MouseRecorder($u, (int) ($args[2] ?? 25));
                        $tu->mouseRecorder->start();
                        $u->sendMessage('You have started a mouse recording for ' . $p->getName() . ' lasting ' . ($args[2] ?? 25) . ' seconds');
                    } elseif($p === null){
                        $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . ' Could not find the player ' . $selectedCheat);
                    }
                    return;
                }
                if($user === null){
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . " Could not find the user $selectedUser");
                } else {
                    $selectedCheat = strtolower($selectedCheat);
                    $sender->sendMessage(TextFormat::BOLD . TextFormat::RED . 'DEBUG DATA' . PHP_EOL . TextFormat::RESET . ($user->debugCache[$selectedCheat] ?? 'NO DATA'));
                }
            }
        }
    }

    /**
     * @return Mockingbird
     */
    public function getPlugin(): Plugin{
        return $this->plugin;
    }

}