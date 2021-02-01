<?php

namespace ethaniccc\Mockingbird\detections;

use ethaniccc\Mockingbird\detections\combat\reach\ReachB;
use ethaniccc\Mockingbird\detections\movement\velocity\VelocityB;
use ethaniccc\Mockingbird\Mockingbird;

abstract class PremiumLoader{

    // Premium checks are given to people that I want to give so haha
    public static function register() : void{
        try{
            $plugin = Mockingbird::getInstance();
            $plugin->availableChecks[] = new ReachB('ReachB', $plugin->getConfig()->exists('ReachB') ? $plugin->getConfig()->get('ReachB') : null);
            $plugin->availableChecks[] = new VelocityB('VelocityB', $plugin->getConfig()->exists('ReachB') ? $plugin->getConfig()->get('ReachB') : null);
        } catch(\Error $e){
            Mockingbird::getInstance()->getLogger()->debug('Premium checks could not be loaded');
        }
    }

}