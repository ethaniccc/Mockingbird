# Mockingbird
Mockingbird is an anti-cheat in development made for fun by an ethic idot - version v2 has
many changes compared to the v1 variants of Mockingbird.

Mockingbird has a test server where you can test your big haxerman hacks on:
```
IP: 104.194.10.127
Port: 25640
```

Special Thanks To:
- shura62
- Blackjack200
- Jonhan
- (discord) @very nice name#6789
- Bavfalcon9

## V2 Changes
**Mockingbird's base inspiration comes from [Neptune](https://github.com/shura62/Neptune/) made by shura62**

TLDR (if you don't care about all the dev stuff): **__Same checks, and new base__**.

Well, first things first - detection modules are no longer event listeners, instead, Detections
extend a Detection class which has a function called "process" which runs every time a packet gets received from the player.

Before Detections process data though, "processors" process data before the check. These processors
handle data and save them into the player's "User" class so all checks can use them. For instance, the FlyA
check gets the User's move delta (vector3) and does math from there.

Every time a player joins, it will register a "User" class for them. All available checks will
have a new instance made from a reflection from Mockingbird's main class made when the plugin enables
and put in a "checks" property in the User. Processors have the same process done.

Why? So I don't have to hardcode checks into a property in the User class.

Mockingbird no longer calls custom events.

There will not be a resetting violation feature unless Mockingbird is still false-punishing users.
Instead, every time a user passes a check, they will be "rewarded". In rewarding, the player's violations
for the check gets multiplied by a very small amount (multiplier varies based off the check). This will help with players which
might false positive some checks at certain points, and is more effective than resetting all the player's violations. 

Custom modules are still here, and now you can also add custom processors. Since I'm too lazy to make
an example, uh, idk just figure it out or wait I guess.

## Detections
This is a list of all the detections Mockingbird has, these detections may not be 100% accurate
and false at sometimes, but the new reward system should compensate.

### Combat Detections
- AutoClicker
    - (A) -> Consistency
    - (B) -> Speed
- KillAura
    - (A) -> MultiAura
- Reach
    - (A) -> Basic Check w/ Location History
- Aim
    - (A) -> Checks data of multiple yaw differences
### Movement Checks
- Fly
    - (A) -> Prediction Check
    - (B) -> Acceleration Check
- Speed
    - (A) -> Friction Check (flags while using bhop)
    - (B) -> Speed Limit Check
- Velocity
    - (A) -> Vertical Check
- GroundSpoof

Mockingbird also has packet checks.

## Custom Stuff
### Custom Processors Docs
**TODO**
### Custom Modules Docs
**TODO**