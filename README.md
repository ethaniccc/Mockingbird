# Mockingbird
Mockingbird is an AntiCheat made for PocketMine servers to prevent the use of unfair
advantage on other players.

**Warning:** In the state Mockingbird is currently in, this may false-positive, especially on production servers with some lag.
This may also false-positive on players who are laggy (e.g: high ping).

#### Special Thanks
* Bavfalcon9
    - Inspo for this project. Also for the structure of this plugin lol. You can check out
    Mavoric (dev) by clicking [here](https://github.com/Bavfalcon9/Mavoric/tree/v2.0.0/)
* shura62
    - Helped with reach checks :D!
* Jonhan
    - Gave **some** checks for Bukkit that I was able to port over to PocketMine.
    You can click [here](https://www.youtube.com/channel/UCZ_Pg7e-1JMlHtqnWw6KIcw) to check out his channel!

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
    and the average TPS the server had when the player got violated.
* Reload Module Command

    **NOTE:** This command intended use is for Custom Modules.
    
    With this command, and the permission `reload_permission` in the config, you can reload custom modules.
    
    If I added a custom module to the `custom_modules` folder, I can use this command to reload and it will register my custom module (yes has been tested).
    Same goes for deleting a custom module.
    
    **Warning:** You cannot reload custom module code with this command.
## Detections
Detections not guaranteed 100% accurate.

### Combat
* Angle
* AutoClicker
    * Consistency Detection
    * Speed Detection
* Reach
* MultiAura
* Toolbox Killaura
### Movement
* AirJump
* Fly (may false-positive on slabs and cobwebs)
* Glide
* InventoryMove (not complete / may be inaccurate)
* FastLadder
* NoSlowdown (while eating)
* NoWeb
* Speed
### Other Detections
* Packet Checks
* ChestStealer
* FastEat
* FastBreak
* Nuker
* Timer (not complete / may be inaccurate)
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