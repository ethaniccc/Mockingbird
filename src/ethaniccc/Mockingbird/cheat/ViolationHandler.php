<?php

namespace ethaniccc\Mockingbird\cheat;

use pocketmine\Server;

final class ViolationHandler{

    /** @var array */
    private static $violations = [];

    /** @var array */
    private static $allViolations = [];

    /** @var array */
    private static $cheatsViolatedFor = [];

    /** @var array */
    private static $tps = [];

    /**
     * @param string $name
     * @param string $cheat
     */
    public static function addViolation(string $name, string $cheat) : void{
        if(!isset(self::$violations[$name])){
            self::$violations[$name] = 0;
        }
        self::$violations[$name] += 1;
        if(!isset(self::$cheatsViolatedFor[$name])){
            self::$cheatsViolatedFor[$name] = [];
        }
        if(!in_array($cheat, self::$cheatsViolatedFor[$name])){
            array_push(self::$cheatsViolatedFor[$name], $cheat);
        }
        if(!isset(self::$tps[$name])){
            self::$tps[$name] = [];
        }
        array_push(self::$tps[$name], Server::getInstance()->getTicksPerSecond());
    }

    /**
     * @param string $name
     * @return int
     */
    public static function getCurrentViolations(string $name) : int{
        return isset(self::$violations[$name]) ? self::$violations[$name] : 0;
    }

    /**
     * @param string $name
     * @return int
     */
    public static function getAllViolations(string $name) : int{
        return isset(self::$allViolations[$name]) ? self::$allViolations[$name] + self::getCurrentViolations($name) : self::getCurrentViolations($name);
    }

    /**
     * @param string $name
     * @param float $violations
     */
    public static function setViolations(string $name, float $violations) : void{
        if(!isset(self::$allViolations[$name])){
            self::$allViolations[$name] = 0;
        }
        self::$allViolations[$name] += self::$violations[$name];
        self::$violations[$name] = $violations;
    }

    /**
     * @param string $name
     * @return array
     */
    public static function getCheatsViolatedFor(string $name) : array{
        return isset(self::$cheatsViolatedFor[$name]) ? self::$cheatsViolatedFor[$name] : [];
    }

    /**
     * @return array
     */
    public static function getSaveData() : array{
        $saveData = [];
        foreach(self::$violations as $name => $violations){
            $saveData[$name] = [
                "CurrentVL" => self::getCurrentViolations($name),
                "TotalVL" => self::getAllViolations($name),
                "AverageTPS" => self::getAverageTPS($name),
                "Cheats" => self::getCheatsViolatedFor($name)
            ];
        }
        return $saveData;
    }

    /**
     * @param string $name
     * @return float
     */
    public static function getAverageTPS(string $name) : float{
        return isset(self::$tps[$name]) ? array_sum(self::$tps[$name]) / count(self::$tps[$name]) : 20;
    }

    /**
     * @param string $name
     * @return array
     */
    public static function getPlayerData(string $name) : array{
        return [
            "Average TPS" => self::getAverageTPS($name),
            "Current Violations" => self::getCurrentViolations($name),
            "Total Violations" => self::getAllViolations($name),
            "Cheats" => self::getCheatsViolatedFor($name)
        ];
    }

}