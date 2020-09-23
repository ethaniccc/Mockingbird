# Mockingbird
Mockingbird is an AntiCheat made for PocketMine servers to prevent the use of unfair
advantage on other players.

**Warning:** In the state Mockingbird is currently in, this may false-positive, especially on production servers with some lag.
This may also false-positive on players who are laggy (e.g: high ping).

#### Special Thanks
* Bavfalcon9
    - Inspo for this project. Also for the structure of this plugin lol. You can check out
    Mavoric (dev) by clicking [here](https://github.com/Bavfalcon9/Mavoric/tree/v2.0.0/) (**back (W)**)
* shura62
    - Helped on Discord with Mockingbird!
* Blackjack200
    - Contributed :p
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
    
    If enabled in the config, the `log` command has two options: a normal
    `/mblogs <player>` or a UI with `/mblogs` without arguments.
    
    The `/mblogs` command will tell you how many violations a player currently has, 
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

    Alias: `mbss`

    **NOTE:** This is to give you the player's view, not to actually be able
    to view the player's screen.
    
    With the permission set in the config, you can use the Mockingbird screenshare
    command, `/mbscreenshare <player>` to screenshare a player. Nobody will be able to 
    see you while you are "screensharing" somebody.
    
    To end a screenshare session, you may do `/mbscreenshare end`
* Alerts Command

    With this command, you can toggle alerts. Just do `/mbalerts`, and if you have alerts enabled, it will disable alerts, same vice-versa.

    When you join you will automatically have alerts enabled.
* Debug Command

    With this command, you can enable debug information about checks - you will need the alert permission to use this command though.

    When you join, you will automatically have debug information off, to toggle debug information, you can use `/mbdebug` and it will enable debug
    if you have it off, and disable if you currently have it on.
## Detections
Detections are not 100% accurate and may false positive sometimes. When reporting a false positive, please give the relevant part of ther debug log for me to look at, along with reproduction steps.
### Combat
* AutoClicker
    * Consistency Detection
    * Speed Detection
* Reach
* MultiAura
### Movement
* AirJump
* Fly
    * FlyA: General prediction check.
    * FlyB: Horizontal and vertical check (extra)
* FastLadder
* NoSlowdown
* NoFall
* Speed
    * SpeedA: Basic speed check
    * SpeedB: Friction check
* Velocity
    * VelocityA: Vertical check
    * VelocityB: **NOT COMPLETE**
### Other Detections
* Packet Checks
* Timer (might be inaccurate sometimes)
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
    
        public function __construct(Mockingbird $plugin,string $cheatName,string $cheatType, ?array $settings){
            parent::__construct($plugin,$cheatName,$cheatType,$settings);
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
    
        public function __construct(Mockingbird $plugin,string $cheatName,string $cheatType, ?array $settings){
            parent::__construct($plugin,$cheatName,$cheatType,$settings);
        }
        
        // personal recommendation to NOT use PlayerMoveEvent and use Mockingbird's custom MoveEvent instead
        // this is because PlayerMoveEvent is synchronous to the server ticks
        public function onMove(PlayerMoveEvent $event) : void{
            // Do your thing here ;p
        }

    }

}
```
You can check the `Cheat` class for all class methods such as `Cheat::fail()`.