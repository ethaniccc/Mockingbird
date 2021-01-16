<?php

namespace ethaniccc\TextCenterFormat;

use pocketmine\utils\TextFormat;

class TextCenterFormat{

    public static function format(string $message, int $length = null) : string{
        $lines = explode(PHP_EOL, $message);
        $message = '';
        $maxKey = count($lines) - 1;
        $i = 0;
        if($length === null){
            $length = 0;
            foreach($lines as $line){
                $length = min(max($length, strlen($line) + 45), 140);
            }
        }
        $length = min($length, 140);
        while($i <= $maxKey){
            $line = $lines[$i];
            unset($lines[$i]);
            $cleaned = TextFormat::clean($line, true);
            $diff = strlen($line) - strlen($cleaned);
            if(($size = strlen($cleaned)) > $length){
                $s = substr($line, $length + $diff, $length + $diff);
                $lines[$i] = $s;
                $i--;
            } elseif($size < $length + $diff){
                $remaining = ($length + $diff) - $size;
                $lastRemaining = $remaining;
                while($remaining !== 0){
                    $remaining % 2 === 0 && $lastRemaining > 2 ? $line = $line . ' ' : $line = ' ' . $line;
                    $remaining--;
                }
            }
            $message .= substr($line, 0, $length + $diff) . PHP_EOL;
            $i++;
        }
        return $message;
    }

}