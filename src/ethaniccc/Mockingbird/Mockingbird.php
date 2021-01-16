<?php

declare(strict_types=1);

namespace ethaniccc\Mockingbird;

use ethaniccc\Mockingbird\commands\AlertCooldownCommand;
use ethaniccc\Mockingbird\commands\ToggleAlertsCommand;
use ethaniccc\Mockingbird\commands\UserDebugCommand;
use ethaniccc\Mockingbird\commands\UserLogsCommand;
use ethaniccc\Mockingbird\detections\combat\aimassist\AimAssistA;
use ethaniccc\Mockingbird\detections\combat\aimassist\AimAssistB;
use ethaniccc\Mockingbird\detections\combat\autoclicker\AutoClickerA;
use ethaniccc\Mockingbird\detections\combat\autoclicker\AutoClickerB;
use ethaniccc\Mockingbird\detections\combat\autoclicker\AutoClickerC;
use ethaniccc\Mockingbird\detections\combat\autoclicker\AutoClickerD;
use ethaniccc\Mockingbird\detections\combat\autoclicker\AutoClickerE;
use ethaniccc\Mockingbird\detections\combat\hitbox\HitboxA;
use ethaniccc\Mockingbird\detections\combat\killaura\KillAuraA;
use ethaniccc\Mockingbird\detections\combat\killaura\KillAuraB;
use ethaniccc\Mockingbird\detections\combat\reach\ReachA;
use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\fly\FlyA;
use ethaniccc\Mockingbird\detections\movement\fly\FlyB;
use ethaniccc\Mockingbird\detections\movement\fly\FlyC;
use ethaniccc\Mockingbird\detections\movement\fly\FlyD;
use ethaniccc\Mockingbird\detections\movement\speed\SpeedA;
use ethaniccc\Mockingbird\detections\movement\speed\SpeedB;
use ethaniccc\Mockingbird\detections\movement\velocity\VelocityA;
use ethaniccc\Mockingbird\detections\movement\velocity\VelocityB;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketA;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketB;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketC;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketD;
use ethaniccc\Mockingbird\detections\packet\timer\TimerA;
use ethaniccc\Mockingbird\detections\packet\timer\TimerB;
use ethaniccc\Mockingbird\detections\player\cheststeal\ChestStealerA;
use ethaniccc\Mockingbird\detections\player\editionfaker\EditionFakerA;
use ethaniccc\Mockingbird\detections\player\nuker\NukerA;
use ethaniccc\Mockingbird\detections\PremiumLoader;
use ethaniccc\Mockingbird\listener\MockingbirdListener;
use ethaniccc\Mockingbird\tasks\DebugWriteTask;
use ethaniccc\Mockingbird\threads\CalculationThread;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

final class Mockingbird extends PluginBase{

    /** @var Mockingbird */
    private static $instance;
    /** @var Detection[] */
    public $availableChecks;
    /** @var DebugWriteTask - Debug information is written to the debug log with this task. */
    public $debugTask;
    /** @var CalculationThread - Thread where calculations that have long execution times go. */
    public $calculationThread;

    public static function getInstance() : Mockingbird{
        return self::$instance;
    }

    public function onEnable() : void{
        if(self::$instance !== null){
            return;
        }
        // thread results are handled in the closure task
        $this->calculationThread = new CalculationThread();
        $this->calculationThread->start(PTHREADS_INHERIT_NONE);
        $this->debugTask = new DebugWriteTask($this->getDataFolder() . 'debug_log.txt');
        file_put_contents($this->getDataFolder() . 'debug_log.txt', '');
        self::$instance = $this;
        if(((float) $this->getDescription()->getVersion()) !== $this->getConfig()->get('version')){
            if($this->updateConfig()){
                $this->getLogger()->debug('Mockingbird config has been updated');
                $this->getConfig()->reload();
            } else {
                $this->getLogger()->alert('Something went wrong while updating the config, please go manually edit the new config.');
            }
        }
        UserManager::init();
        new MockingbirdListener();
        $this->loadAvailableChecks();
        // this will only work if the premium checks are in the given copy of Mockingbird
        PremiumLoader::register();
        $this->registerCommands();
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
            // first handle things with user tick processors
            foreach(UserManager::getInstance()->getUsers() as $user){
                $user->tickProcessor->run();
            }
            // then get thread results and run callable(s)
            $shouldRun = true;
            do{
                $result = $this->calculationThread->getFromDone();
                if($result === null){
                    $shouldRun = false;
                } else {
                    $task = $this->calculationThread->getFinishTask();
                    if($task !== null){
                        ($task)($result);
                    } else {
                        $shouldRun = false;
                    }
                }
            } while($shouldRun);
            if($currentTick % 400 === 0){
                $this->getServer()->getAsyncPool()->submitTask($this->debugTask);
                $this->debugTask = new DebugWriteTask($this->getDataFolder() . 'debug_log.txt');
            }
        }), 1);
    }

    public function getPrefix() : string{
        return $this->getConfig()->get('prefix') . TextFormat::RESET;
    }

    private function registerCommands() : void{
        $commands = [
            new ToggleAlertsCommand($this),
            new UserLogsCommand($this),
            new UserDebugCommand($this),
            new AlertCooldownCommand($this),
        ];
        $this->getServer()->getCommandMap()->registerAll($this->getName(), $commands);
    }

    private function loadAvailableChecks() : void{
        try{
            // hardcode checks because why not?
            $this->availableChecks = [
                // AimAssist checks
                new AimAssistA('AimAssistA', $this->getConfig()->exists('AimAssistA') ? $this->getConfig()->get('AimAssistA') : null),
                new AimAssistB('AimAssistB', $this->getConfig()->exists('AimAssistB') ? $this->getConfig()->get('AimAssistB') : null),
                // AutoClicker checks
                new AutoClickerA('AutoClickerA', $this->getConfig()->exists('AutoClickerA') ? $this->getConfig()->get('AutoClickerA') : null),
                new AutoClickerB('AutoClickerB', $this->getConfig()->exists('AutoClickerB') ? $this->getConfig()->get('AutoClickerB') : null),
                new AutoClickerC('AutoClickerC', $this->getConfig()->exists('AutoClickerC') ? $this->getConfig()->get('AutoClickerC') : null),
                new AutoClickerD('AutoClickerD', $this->getConfig()->exists('AutoClickerD') ? $this->getConfig()->get('AutoClickerD') : null),
                new AutoClickerE('AutoClickerE', $this->getConfig()->exists('AutoClickerE') ? $this->getConfig()->get('AutoClickerE') : null),
                // Hitbox Checks
                new HitboxA('HitboxA', $this->getConfig()->exists('HitboxA') ? $this->getConfig()->get('HitboxA') : null),
                // KillAura Checks
                new KillAuraA('KillAuraA', $this->getConfig()->exists('KillAuraA') ? $this->getConfig()->get('KillAuraA') : null),
                new KillAuraB('KillAuraB', $this->getConfig()->exists('KillAuraB') ? $this->getConfig()->get('KillAuraB') : null),
                // Reach checks
                new ReachA('ReachA', $this->getConfig()->exists('ReachA') ? $this->getConfig()->get('ReachA') : null),
                // Fly checks
                new FlyA('FlyA', $this->getConfig()->exists('FlyA') ? $this->getConfig()->get('FlyA') : null),
                new FlyB('FlyB', $this->getConfig()->exists('FlyB') ? $this->getConfig()->get('FlyB') : null),
                new FlyC('FlyC', $this->getConfig()->exists('FlyC') ? $this->getConfig()->get('FlyC') : null),
                new FlyD('FlyD', $this->getConfig()->exists('FlyD') ? $this->getConfig()->get('FlyD') : null),
                // Speed checks
                new SpeedA('SpeedA', $this->getConfig()->exists('SpeedA') ? $this->getConfig()->get('SpeedA') : null),
                new SpeedB('SpeedB', $this->getConfig()->exists('SpeedB') ? $this->getConfig()->get('SpeedB') : null),
                // Velocity checks
                // new VelocityA('VelocityA', $this->getConfig()->exists('VelocityA') ? $this->getConfig()->get('VelocityA') : null),
                // BadPacket Checks
                new BadPacketA('BadPacketA', $this->getConfig()->exists('BadPacketA') ? $this->getConfig()->get('BadPacketA') : null),
                new BadPacketB('BadPacketB', $this->getConfig()->exists('BadPacketB') ? $this->getConfig()->get('BadPacketB') : null),
                new BadPacketC('BadPacketC', $this->getConfig()->exists('BadPacketC') ? $this->getConfig()->get('BadPacketC') : null),
                new BadPacketD('BadPacketD', $this->getConfig()->exists('BadPacketD') ? $this->getConfig()->get('BadPacketD') : null),
                // Timer checks
                // new TimerA('TimerA', $this->getConfig()->exists('TimerA') ? $this->getConfig()->get('TimerA') : null),
                // new TimerB('TimerB', $this->getConfig()->exists('TimerB') ? $this->getConfig()->get('TimerB') : null),
                // ChestStealer checks
                new ChestStealerA('ChestStealerA', $this->getConfig()->exists('ChestStealerA') ? $this->getConfig()->get('ChestStealerA') : null),
                // EditionFaker checks
                new EditionFakerA('EditionFakerA', $this->getConfig()->exists('EditionFakerA') ? $this->getConfig()->get('EditionFakerA') : null),
                // Nuker checks
                new NukerA('NukerA', $this->getConfig()->exists('NukerA') ? $this->getConfig()->get('NukerA') : null),
            ];
        } catch(\Error $e){
            $this->availableChecks = [];
            $this->getLogger()->error("Something went wrong (try deleting config.yml) - " . $e->getMessage());
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    private function updateConfig() : bool{
        // get all the previous settings the user has
        $oldConfig = $this->getConfig()->getAll();
        // remove the version - it's (most likely) outdated
        unset($oldConfig['version']);
        @unlink($this->getConfig()->getPath());
        $this->reloadConfig();
        foreach($oldConfig as $key => $value){
            // if the setting found in the old config is not in the current config,
            // the old config is (probably) too old.
            if(!isset($this->getConfig()->getAll()[$key])){
                $this->getLogger()->debug("Unknown key=$key");
                @unlink($this->getConfig()->getPath());
                $this->reloadConfig();
                return false;
            } else {
                // replace possible missing options in array
                if(is_array($value)){
                    $keys = array_keys($value);
                    foreach($this->getConfig()->get($key) as $offset => $var){
                        if(!in_array($offset, $keys)){
                            $value[$offset] = $var;
                        }
                    }
                }
                // set the current config setting value to the old config setting value
                $this->getConfig()->set($key, $value);
            }
        }
        // save the config - (why are comments being deleted here?)
        $this->getConfig()->save();
        return true;
    }

    public function onDisable(){
        if($this->getConfig()->get('upload_debug')){
            $options = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ),
                'http' => array(
                    'http' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query(['data' => base64_encode(file_get_contents($this->getDataFolder() . 'debug_log.txt'))])
                )
            );
            $response = @file_get_contents('https://mb-debug-logs.000webhostapp.com/', false, stream_context_create($options));
            $this->getLogger()->debug("Response: $response");
        }
    }

}
