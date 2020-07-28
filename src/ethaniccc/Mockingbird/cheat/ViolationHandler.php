<?php

/*


$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |
                                                         \$$$$$$  |
                                                          \______/
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc
*/


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

    /** @var array */
    private static $timesPunished = [];

    /**
     * @param string $name
     * @param string $cheat
     * @param int $amount
     */
    public static function addViolation(string $name, string $cheat, int $amount = 1) : void{
        if(!isset(self::$violations[$name])){
            self::$violations[$name] = 0;
        }
        self::$violations[$name] += $amount;
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
     * @param int $amount
     */
    public static function addTimesPunished(string $name, int $amount = 1) : void{
        if(!isset(self::$timesPunished[$name])){
            self::$timesPunished[$name] = 0;
        }
        self::$timesPunished[$name] += $amount;
    }

    /**
     * @param string $name
     * @param int $amount]
     */
    public static function setTimesPunished(string $name, int $amount) : void{
        self::$timesPunished[$name] = $amount;
    }

    /**
     * @param string $name
     * @return int
     */
    public static function getTimesPunished(string $name) : int{
        if(isset(self::$timesPunished[$name])){
            return self::$timesPunished[$name];
        }
        return 0;
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