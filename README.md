# Mockingbird

Mockingbird is an AntiCheat made for PocketMine servers to prevent the use of unfair
advantage on other players.

**Warning:** In the state Mockingbird is currently in, this may false-positive, especially on production servers with some lag.
This may also false-positive on players who are laggy (e.g: high ping).

### Test Server
Mockingbird has a test server - here are the details if you want to join:

```
IP: mockingbird.mcpro.io
Port: 40647
```

## Detections

Detections not guaranteed 100% accurate.

### Combat
* AutoClicker
    * Consistency Detection
    * Speed Detection
* Reach
* Toolbox Killaura
### Movement
* AirJump
* Fly
* Glide
* InventoryMove
* FastLadder
* NoSlowdown (while eating)
* NoWeb
* Speed (not complete)
### Other Detections
* Packet Checks
* ChestStealer
* FastEat
* FastBreak
* Nuker
## Custom Modules
A feature that Mockingbird has is Custom Modules, which you can use to
make new checks that don't currently exist, or to override a check with a 
better check. You can even modify [Mavoric](https://github.com/Bavfalcon9/Mavoric/tree/v2.0.0) checks
to work with Mockingbird (click [here](https://github.com/ethaniccc/Mockingbird/blob/master/resources/custom_modules/MavoricSpeedA.php) for an example)!

To make a custom module, make a new PHP file with the name of the file correlating
to the class name of the file:

E.G: In **NewSpeed.php**:
```php
<?php

namespace ethaniccc\Mockingbird\cheat\custom{

    use ethaniccc\Mockingbird\Mockingbird;
    use ethaniccc\Mockingbird\cheat\Cheat;
    use pocketmine\event\player\PlayerMoveEvent;

    class NewSpeed extends Cheat{
    
        public function __construct(Mockingbird $plugin,string $cheatName,string $cheatType,bool $enabled = true){
            parent::__construct($plugin,$cheatName,$cheatType,$enabled);
        }

    }

}
```

Then from there, since the `Cheat` class implements `Listener`, you can make your own detections!
```php
<?php

namespace ethaniccc\Mockingbird\cheat\custom{

    use ethaniccc\Mockingbird\Mockingbird;
    use ethaniccc\Mockingbird\cheat\Cheat;
    use pocketmine\event\player\PlayerMoveEvent;

    class NewSpeed extends Cheat{
    
        public function __construct(Mockingbird $plugin,string $cheatName,string $cheatType,bool $enabled = true){
            parent::__construct($plugin,$cheatName,$cheatType,$enabled);
        }
        
        public function onMove(PlayerMoveEvent $event) : void{
            // Do your thing here ;)
        }

    }

}
```