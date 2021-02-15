<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use pocketmine\math\Vector2;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MouseRecorder extends AsyncTask{

    public $isRunning;

    private $width;
    private $height;
    private $maxTicks;
    private $runTicks = 0;
    private $path;
    private $rotations = [];
    /** @var Pair */
    private $origin;
    private $clicks = [];

    private const CLICK_DOT_RADIUS = 0.7;
    private static $adminStorage = [];

    public function __construct(User $admin, string $path, int $seconds, int $resolution){
        self::$adminStorage[spl_object_hash($this)] = $admin;
        $this->width = 360 * $resolution; $this->height = 180 * $resolution;
        $this->maxTicks = $seconds * 20;
        $this->path = $path;
        $this->origin = new Pair($this->width, $this->height);
        $this->isRunning = false;
    }

    public function start() : void{
        $this->isRunning = true;
    }

    public function handleRotation(float $yawDelta, float $pitchDelta) : void{
        $this->rotations[] = new Pair($yawDelta, $pitchDelta);
        ++$this->runTicks;
    }

    public function handleClick() : void{
        $this->clicks[] = ($var = count($this->rotations) - 1) > 0 ? $var : 0;
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
        $image = imagecreate($this->width, $this->height);
        $backgroundColor = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $backgroundColor);
        $this->renderClicks($image);
        imagepng($image, $this->path, 4);
    }

    private function renderClicks($image) : void{
        $color = imagecolorallocatealpha($image, 0, 255, 0, 102);
        // var_dump($this->width, $this->height);
        $currentCord = new Vector2($this->origin->getX(), $this->origin->getY());
        foreach((array) $this->clicks as $tick){
            $x1 = $currentCord->getX();
            $y1 = $currentCord->getY();
            $resolution = $this->width / 360;
            $x2 = $x1 + (((array) $this->rotations)[$tick])->getX();
            $y2 = $y1 + (((array) $this->rotations)[$tick])->getY();

            imagefilledellipse($image, (int)(($x1 - self::CLICK_DOT_RADIUS) * $resolution), (int)(($y1 - self::CLICK_DOT_RADIUS) * $resolution), (int)(5 * self::CLICK_DOT_RADIUS * $resolution), (int)(5 * self::CLICK_DOT_RADIUS * $resolution), $color);
            $currentCord->x = $x2; $currentCord->y = $y2;

            if($x2 >= $this->width){
                $currentCord->x = fmod($currentCord->getX(), $this->width);
            } elseif($x2 < 0){
                $currentCord->x = fmod($this->width + $x2, $this->width);
            }

            if($y2 >= $this->height){
                $currentCord->y = fmod($x2, $this->height);
            } elseif($y2 < 0){
                $currentCord->y = fmod($this->height + $y2, $this->height);
            }
        }
    }

    public function onCompletion(Server $server){
        [$u, $admin] = $this->fetchLocal();
        $admin->sendMessage('Mouse recording image for ' . $u->player->getName() . ' is now available');
        // kermit
        $u->mouseRecorder = null;
    }

}