<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use pocketmine\math\Vector2;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MouseRecorder extends AsyncTask{

    public $isRunning;
    private $maxTicks;
    private $runTicks = 0;
    private $rotations = [];
    private static $adminStorage = [];

    public function __construct(User $admin, int $seconds){
        self::$adminStorage[spl_object_hash($this)] = $admin;
        $this->maxTicks = $seconds * 20;
        $this->isRunning = false;
    }

    public function start() : void{
        $this->isRunning = true;
    }

    public function handleRotation(float $yawDelta, float $pitchDelta) : void{
        $this->rotations[] = new Pair($yawDelta, $pitchDelta);
        ++$this->runTicks;
    }

    public function getPercentage() : float{
        return ($this->runTicks / $this->maxTicks) * 100;
    }

    public function isFinished() : bool{
        return $this->runTicks >= $this->maxTicks;
    }

    public function finish(User $user) : void{
        $this->storeLocal([$user, $this->getAdmin()]);
        $this->isRunning = false;
        Server::getInstance()->getAsyncPool()->submitTask($this);
    }

    public function getAdmin() : ?User{
        return $this->isRunning ? self::$adminStorage[spl_object_hash($this)] : null;
    }

    public function onRun(){
        $values = [];
        foreach((array)$this->rotations as $pair){
            /** @var Pair $pair */
            $values[] = [$pair->getX(), $pair->getY()];
        }
        $options = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
            'http' => array(
                'http' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query(['data' => serialize($values)])
            )
        );
        $response = @file_get_contents("https://mb-debug-logs.000webhostapp.com/create_graph.php", false, stream_context_create($options));
        $this->setResult($response);
    }

    public function onCompletion(Server $server){
        [$u, $admin] = $this->fetchLocal();
        $result = $this->getResult();
        $admin->sendMessage($result);
        // kermit
        $u->mouseRecorder = null;
    }

}