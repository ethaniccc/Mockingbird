<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class PacketUtils{

    public static function playerAuthToMovePlayer(PlayerAuthInputPacket $packet, User $user) : MovePlayerPacket{
        $movePk = new MovePlayerPacket();
        $movePk->entityRuntimeId = $user->player->getId();
        $movePk->mode = MovePlayerPacket::MODE_NORMAL;
        $movePk->position = $packet->getPosition();
        $movePk->pitch = $packet->getPitch();
        $movePk->yaw = $packet->getYaw();
        $movePk->headYaw = $packet->getHeadYaw();
        $movePk->onGround = LevelUtils::userIsOnGround($user);
        return $movePk;
    }

}