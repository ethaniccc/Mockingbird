<?php

namespace ethaniccc\Mockingbird\detections;

use ethaniccc\Mockingbird\detections\combat\reach\ReachB;
use ethaniccc\Mockingbird\detections\movement\velocity\VelocityB;
use ethaniccc\Mockingbird\detections\packet\badpackets\BadPacketF;
use ethaniccc\Mockingbird\Mockingbird;

abstract class PremiumLoader{

    // Premium checks are given to people that I want to give so haha
    public static function register() : void{
        try{
            $plugin = Mockingbird::getInstance();
            /**
             * Commented out code means the check is probably not ready to be in use yet.
             * E.G - Right now, VelocityB is only debug because I'm bad.
             */
            // $plugin->availableChecks[] = new ReachB('ReachB', $plugin->getConfig()->get('ReachB', null));
            // $plugin->availableChecks[] = new VelocityB('VelocityB', $plugin->getConfig()->get('VelocityB', null));
            $plugin->availableChecks[] = new BadPacketF('BadPacketF',$plugin->getConfig()->get('BadPacketF', null));
        } catch(\Error $e){
            Mockingbird::getInstance()->getLogger()->debug('Premium checks could not be loaded');
        }
    }

}