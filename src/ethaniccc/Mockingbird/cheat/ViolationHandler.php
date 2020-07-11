<?php

namespace ethaniccc\Mockingbird\cheat;

final class ViolationHandler{

    private static $violations = [];
    private static $allViolations = [];
    private static $cheatsViolatedFor = [];

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
    }

    public static function getCurrentViolations(string $name) : int{
        return isset(self::$violations[$name]) ? self::$violations[$name] : 0;
    }

    public static function getAllViolations(string $name) : int{
        return isset(self::$allViolations[$name]) ? self::$allViolations[$name] + self::getCurrentViolations($name) : self::getCurrentViolations($name);
    }

    public static function setViolations(string $name, float $violations) : void{
        if(!isset(self::$allViolations[$name])){
            self::$allViolations[$name] = 0;
        }
        self::$allViolations[$name] += self::$violations[$name];
        self::$violations[$name] = $violations;
    }

    public static function getCheatsViolatedFor(string $name) : array{
        return isset(self::$cheatsViolatedFor[$name]) ? self::$cheatsViolatedFor[$name] : [];
    }

    public static function getSaveData() : array{
        $saveData = [];
        foreach(self::$violations as $name => $violations){
            $saveData[$name] = [
                "Violations" => self::getCurrentViolations($name),
                "Cheats" => self::getCheatsViolatedFor($name)
            ];
        }
        return $saveData;
    }

    public static function getPlayerData(string $name) : array{
        return [
            "Current Violations" => self::getCurrentViolations($name),
            "Total Violations" => self::getAllViolations($name),
            "Cheats" => self::getCheatsViolatedFor($name)
        ];
    }

}