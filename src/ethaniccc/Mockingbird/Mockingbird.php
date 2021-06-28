<?php

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
use ethaniccc\Mockingbird\detections\movement\omnisprint\OmniSprintA;
use ethaniccc\Mockingbird\detections\movement\speed\SpeedA;
use ethaniccc\Mockingbird\detections\movement\speed\SpeedB;
use ethaniccc\Mockingbird\detections\movement\velocity\VelocityA;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketA;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketB;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketC;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketD;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketE;
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
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\TextFormat;

final class Mockingbird extends PluginBase{

    /** @var Mockingbird */
    private static $instance;
    /** @var Detection[] - A list of detections that will be used. */
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
        $notifier = new SleeperNotifier();
        $this->calculationThread = new CalculationThread($this->getServer()->getLogger(), $notifier);
        $this->calculationThread->start(PTHREADS_INHERIT_NONE);
        $this->getServer()->getTickSleeper()->addNotifier($notifier, function() : void{
            $shouldRun = true;
            do{
                $task = $this->calculationThread->getFinishTask(false);
                if($task !== false){
                    $result = $this->calculationThread->getFromDone(false);
                    if($result !== false){
                        ($this->calculationThread->getFinishTask())($this->calculationThread->getFromDone());
                    } else {
                        $shouldRun = false;
                    }
                } else {
                    $shouldRun = false;
                }
            } while($shouldRun);
            // $this->getLogger()->debug('tick=' . $this->getServer()->getTick());
        });
        $this->debugTask = new DebugWriteTask($this->getDataFolder() . 'debug_log.txt');
        file_put_contents($this->getDataFolder() . 'debug_log.txt', 'This server is using version ' . $this->getDescription()->getVersion() . ' of Mockingbird' . PHP_EOL);
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
        MathUtils::init();
        new MockingbirdListener();
        $this->loadDefaultChecks();
        // this will only work if the premium checks are in the given copy of Mockingbird
        PremiumLoader::register();
        $this->registerCommands();
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
            // first handle things with user tick processors
            foreach(UserManager::getInstance()->getUsers() as $user){
                $user->tickProcessor->run($user);
            }
            if($currentTick % 400 === 0){
                $this->getServer()->getAsyncPool()->submitTask($this->debugTask);
                $this->debugTask = new DebugWriteTask($this->getDataFolder() . 'debug_log.txt');
            }
            $this->calculationThread->handleServerTick();
        }), 1);
        @mkdir($this->getDataFolder() . 'packet_logs');
        @mkdir($this->getDataFolder() . 'mouse_recordings');
    }

    public function getPrefix() : string{
        return $this->getConfig()->get('prefix') . TextFormat::RESET;
    }

    /**
     * @param Detection[] $detections
     * This function was made for any external plugins that want to add custom checks.
     * These custom checks must be constructed with their name for the first parameter, and
     * the second parameter null, unless there is a config for those checks in the plugin using this
     * function.
     */
    public function registerCustomChecks(array $detections) : void{
        foreach($detections as $detection){
            $this->getLogger()->debug('Registering detection ' . $detection->name . ' class ' . get_class($detection));
            $this->availableChecks[] = $detection;
        }
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

    private function loadDefaultChecks() : void{
        // hardcode checks because why not?
        $this->availableChecks = [
            // AimAssist checks
            new AimAssistA('AimAssistA', $this->getConfig()->get('AimAssistA', null)),
            new AimAssistB('AimAssistB', $this->getConfig()->get('AimAssistB', null)),
            // AutoClicker checks
            new AutoClickerA('AutoClickerA', $this->getConfig()->get('AutoClickerA', null)),
            new AutoClickerB('AutoClickerB', $this->getConfig()->get('AutoClickerB', null)),
            new AutoClickerC('AutoClickerC', $this->getConfig()->get('AutoClickerC', null)),
            new AutoClickerD('AutoClickerD', $this->getConfig()->get('AutoClickerD', null)),
            new AutoClickerE('AutoClickerE', $this->getConfig()->get('AutoClickerE', null)),
            // Hitbox Checks
            new HitboxA('HitboxA', $this->getConfig()->get('HitboxA', null)),
            // KillAura Checks
            new KillAuraA('KillAuraA', $this->getConfig()->get('KillAuraA', null)),
            new KillAuraB('KillAuraB', $this->getConfig()->get('KillAuraB', null)),
            // Reach checks
            new ReachA('ReachA', $this->getConfig()->get('ReachA', null)),
            // Fly checks
            new FlyA('FlyA', $this->getConfig()->get('FlyA', null)),
            new FlyB('FlyB', $this->getConfig()->get('FlyB', null)),
            new FlyC('FlyC', $this->getConfig()->get('FlyC', null)),
            new FlyD('FlyD', $this->getConfig()->get('FlyD', null)),
            // Speed checks
            new SpeedA('SpeedA', $this->getConfig()->get('SpeedA', null)),
            new SpeedB('SpeedB', $this->getConfig()->get('SpeedB', null)),
            // Velocity checks
            new VelocityA('VelocityA', $this->getConfig()->get('VelocityA', null)),
            // OmiSprint checks
            new OmniSprintA('OmniSprintA', $this->getConfig()->get('OmniSprintA', null)),
            // BadPacket Checks
            new BadPacketA('BadPacketA', $this->getConfig()->get('BadPacketA', null)),
            new BadPacketB('BadPacketB', $this->getConfig()->get('BadPacketB', null)),
            new BadPacketC('BadPacketC', $this->getConfig()->get('BadPacketC', null)),
            new BadPacketD('BadPacketD', $this->getConfig()->get('BadPacketD', null)),
            new BadPacketE('BadPacketE', $this->getConfig()->get('BadPacketE', null)),
            // Timer checks
            new TimerA('TimerA', $this->getConfig()->get('TimerA', null)),
            // new TimerB('TimerB', $this->getConfig()->exists('TimerB') ? $this->getConfig()->get('TimerB') : null),
            // ChestStealer checks
            // new ChestStealerA('ChestStealerA', $this->getConfig()->get('ChestStealerA', null)),
            // EditionFaker checks
            new EditionFakerA('EditionFakerA', $this->getConfig()->get('EditionFakerA', null)),
            // Nuker checks
            new NukerA('NukerA', $this->getConfig()->get('NukerA', null)),
        ];
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
