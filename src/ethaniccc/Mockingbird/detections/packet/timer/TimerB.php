<?php

namespace ethaniccc\Mockingbird\detections\packet\timer;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;

class TimerB extends Detection{

    private $samples = [];
    private $lastTick = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->lastTick = Server::getInstance()->getTick();
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $speed = Server::getInstance()->getTick() - $this->lastTick;
            $this->samples[] = $speed;
            if(count($this->samples) === 40){
                $deviation = floor(MathUtils::getDeviation($this->samples));
                if($deviation > 12){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "deviation=$deviation");
                    }
                } else {
                    $this->preVL = 0;
                }
                $this->samples = [];
            }
        }
    }

}