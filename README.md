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
## Commands
* Log Command
    
    If enabled in the config, the `/log` command has two options: a normal
    `/logs <player>` or a UI with `/logs` without arguments.
    
    The `/log` command will tell you how many violations a player currently has, 
    how many violations they have in total (when a player gets punished their current violation count resets to 0),
    The Average TPS the server had when the player got violated, and 

* Report Command

    **NOTE:** After reporting a player, the reporter must wait 60 seconds before making a new report.
    
    If enabled in the config, the `/mbreport` command will bring up a UI with a list of online players. From there, you can
    click on a player, then brought to a new UI where you will be able to select which cheat you want to report the player
    for.
    
    If the player has failed the check and has more than 10 violations, Mockingbird will alert staff and ask for a staff member to check on the situation.
    
    If the player has not failed the check, Mockingbird will schedule a task 30 seconds in advance and see if the player has failed the check. If the player has still not failed
    the reported check within the 30 seconds, it will notify the reporter that there was no evidence found of the accused cheat. However, if a player has failed the check within the
    next 30 seconds, Mockingbird will alert staff and ask for someone to check on the situation.
    
* Reload Module Command

    **NOTE:** This command intended use is for Custom Modules.
    
    With this command, and the permission `reload_permission` in the config, you can reload custom modules.
    
    If I added a custom module to the `custom_modules` folder, I can use this command to reload and it will register my custom module (yes has been tested).
    Same goes for deleting a custom module.
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