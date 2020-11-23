<?php

namespace ethaniccc\Mockingbird\detections\player\editionfaker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class EditionFakerA extends Detection{

    private $faking = false;
    private $givenOS;
    // private $realOS = ['win10' => '896928775', 'mobile' => '1739947436', 'Nintendo' => '2047319603'];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof LoginPacket){
            // finally the reign of using Horion's EditionFaker to fucking bypass some combat checks is finally over
            if($user->win10 && !$user->isDesktop){
                $this->faking = true;
            }
            $this->givenOS = $packet->clientData["DeviceOS"];
        } elseif($packet instanceof PlayerAuthInputPacket && $this->faking && $user->loggedIn){
            $this->fail($user, "givenOS={$this->givenOS} realOS=win10");
        }
    }

}