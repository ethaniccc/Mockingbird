# Mockingbird
Mockingbird is an anti-cheat in development made for fun by an ethic idot - version v2 has
many changes compared to the v1 variants of Mockingbird.

Important Notes:
- **You want a decent server to handle everything Mockingbird is going to do with the least amount of false positives (such as WitherHosting's $1.25 plan).**
- **Waterdog may bring up issues while using Mockingbird, if you use Waterdog along with Mockingbird, know that things may go wrong.**

Here's something I want to relay before moving forward:
1) If you have an issue with Mockingbird (constant falsing, too much cpu usage, etc.) **please** make an issue
on the GitHub repository with details, so I can fix it. You can leave a bad review but **please** make an issue :)
2) Find a bypass (for movement detections only)? Make an issue on the GitHub repository with a video.
3) Got a feature suggestion? Don't put it in reviews - make an issue on the GitHub repository.

Mockingbird no longer has a test server to test because I am poor.

Special Thanks To:
- shura62
- Blackjack200
- Jonhan
- (discord) @very nice name#6789
- Bavfalcon9

## V2 Changes
**Mockingbird's base inspiration comes from [Neptune](https://github.com/shura62/Neptune/) made by shura62**

TLDR (if you don't care about all the dev stuff): **__Same checks, and new base__**.
TLDR List:
- New Base
- Same and new checks
- More accurate
- Cheat probability
- Reward system (for when players pass checks to prevent falses)
- Less CPU Usage

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

Detections now have "cheat probability". What this will do is estimate the chance of cheating.
This is determined by how many times a player flags a certain check a certain amount of times within a period.

Custom modules are still here, and now you can also add custom processors. Since I'm too lazy to make
an example, uh, idk just figure it out or wait I guess.

## Detections
This is a list of all the detections Mockingbird has, these detections may not be 100% accurate
and false at sometimes, but the new reward system should compensate.

### Combat Detections
- Aim
    - (A) -> Yaw delta to pitch delta check
    - (B) -> GCD and delta comparison check
- AutoClicker
    - (A) -> Consistency
    - (B) -> Speed
    - (C) -> Statistics
    - (D) -> Duplicated Statistics
- KillAura
    - (A) -> MultiAura
    - (B) -> NoSwing
- Reach
    - (A) -> Colliding Ray Check w/ Location History
- Hitbox
    - (A) -> Colliding Ray Check
### Movement Checks
- Fly
    - (A) -> Prediction Check
    - (B) -> AirJump Check
    - (C) -> Acceleration Check
- Speed
    - (A) -> Friction Check (flags while using bhop and some other hacks)
    - (B) -> Speed Limit Check
- Velocity
    - (A) -> Vertical Check (**99% by default**)
    - (B) -> Horizontal Check (**95% by default**)
### Player Checks
- Nuker (yep lag compensated in less than 40 lines)
- ChestStealer (yep also lag compensated in less than 40 lines)
- EditionFaker (**pog**)


Mockingbird also has packet checks.
- BadPackets (checks for validity of packets sent)
    * (A) -> Pitch validity check
    * (B) -> MovePlayerPacket consistency check
    * (C) -> Checks if player hits themselves (can be used to bypass some checks?)
- Timer (checks if player is sending too many packets in an instance)
    - (A) -> Balance Check (bad with server lag)

## Custom Stuff
### Custom Processors Docs
**TODO: If someone wants to pull request for this please do so by all means!**
### Custom Modules Docs
**TODO: If someone wants to pull request for this please do so by all means!**