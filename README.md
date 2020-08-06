# Mockingbird
Mockingbird is an AntiCheat made for PocketMine servers to prevent the use of unfair
advantage on other players.

**Warning:** In the state Mockingbird is currently in, this may false-positive, especially on production servers with some lag.
This may also false-positive on players who are laggy (e.g: high ping).

#### Special Thanks
* Bavfalcon9
    - Inspo for this project. Also for the structure of this plugin lol. You can check out
    Mavoric (dev) by clicking [here](https://github.com/Bavfalcon9/Mavoric/tree/v2.0.0/) (**archived :d**)
* shura62
    - Helped on Discord with Mockingbird!
* Jonhan
    - Gave **some** checks for Bukkit that I was able to port over to PocketMine.
    You can click [here](https://www.youtube.com/channel/UCZ_Pg7e-1JMlHtqnWw6KIcw) to check out his channel!

### Test Server
Mockingbird has a test server - here are the details if you want to join:
```
IP: 104.194.10.127
Port: 25640
```
You can also click [here](https://discord.gg/v77FESn) to join my discord.
## Commands
* Log Command
    
    If enabled in the config, the `/log` command has two options: a normal
    `/logs <player>` or a UI with `/logs` without arguments.
    
    The `/log` command will tell you how many violations a player currently has, 
    how many violations they have in total (when a player gets punished their current violation count resets to 0),
    and the average TPS the server had when the player got violated.
    
* Enable Module Command
    
    With the `/mbenable` command, you can enable certain modules in-game. For example,
    if I forgot to turn on `InventoryMove` in the config, I could use `/mbenable inventorymove` to 
    enable it. If the module is enabled already, the plugin you tell you so. If you want to add and enable
    a new custom module, you must use `/mbreload`.
    
* Disable Module Command

    With the `/mbdisable` command, you can disable certain modules in-game. For example, if
    `AutoClickerA` checks are falsing too much, you can disable it with `/mbdisable autoclickera`. If the specified
    module is disabled already, the plugin will tell you so. 
    
* Reload Module Command

    **NOTE:** This command intended use is for Custom Modules.
    
    With this command, and the permission `reload_permission` in the config, you can reload custom modules.
    
    If I added a custom module to the `custom_modules` folder, I can use this command to reload and it will register my custom module (yes has been tested).
    Same goes for deleting a custom module.
    
    **Warning:** You cannot reload custom module code with this command.
* "Screenshare" command

    **NOTE:** This is to give you the player's view, not to actually be able
    to view the player's screen.
    
    With the permission set in the config, you can use the Mockingbird screenshare
    command, `/mbscreenshare <player>` to screenshare a player. Nobody will be able to 
    see you while you are "screensharing" somebody.
* Alerts Command

    With this command, you can toggle alerts. Just do `/mbalerts`, and if you have alerts enabled, it will disable alerts, same vice-versa.

    When you join you will automatically have alerts enabled.
* Debug Command

    With this command, you can enable debug information about checks - you will need the alert permission to use this command though.

    When you join, you will automatically have debug information off, to toggle debug information, you can use `/mbdebug` and it will enable debug
    if you have it off, and disable if you currently have it on.
## Detections
Detections not guaranteed 100% accurate.

### Combat
* Angle
* AutoClicker
    * Consistency Detection (may sometimes false?)
    * Speed Detection
* Reach
    * ReachA: Uses ray tracing to get the distance from the
    damager to the target. This check will not work for mobile players.
    * ReachB: Uses XZ location distance to check the distance between two players.
    This is inefficent and is only intended for use to make a reach check with mobile players
    using reach.
* MultiAura
* Toolbox Killaura
    * Also a NoSwing check :p
* Hitbox
    - This check is not complete and should **not** be used. Especially on production servers. This check
    will only give debug output.
### Movement
* AirJump
* Fly
* Glide
* InventoryMove (not complete / may be inaccurate)
* FastLadder
* NoSlowdown (while eating)
* NoWeb
* NoFall
* Speed
    * SpeedA: Basic speed check.
    * SpeedB: Mini-Bhop check
### Other Detections
* Packet Checks
* ChestStealer
* FastEat
* FastBreak (not complete / may be inaccurate)
* Nuker
* Timer (not complete / may be inaccurate)
* EditionFaker
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
        
        // personal recommendation to NOT use PlayerMoveEvent
        public function onMove(PlayerMoveEvent $event) : void{
            // Do your thing here ;)
        }

    }

}
```
You can check the `Cheat` class for all class methods.