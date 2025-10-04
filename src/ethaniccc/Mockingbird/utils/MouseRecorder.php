<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use pocketmine\math\Vector2;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MouseRecorder extends AsyncTask{

<<<<<<< HEAD
	public bool $isRunning;

	private int $width;
	private int $height;
	private int $maxTicks;
	private int $runTicks = 0;
	private string $path;
	private array $rotations = [];
	/** @var Pair */
	private Pair $origin;
	private array $clicks = [];

	private const CLICK_DOT_RADIUS = 0.7;
	private static array $adminStorage = [];

	public function __construct(User $admin, string $path, int $seconds, int $resolution){
		self::$adminStorage[spl_object_hash($this)] = $admin;
		$this->width = 360 * $resolution;
		$this->height = 180 * $resolution;
		$this->maxTicks = $seconds * 20;
		$this->path = $path;
		$this->origin = new Pair($this->width, $this->height);
		$this->isRunning = false;
	}
=======
    public $isRunning;
    private $maxTicks;
    private $runTicks = 0;
    private $rotations = [];
    private static $adminStorage = [];

    public function __construct(User $admin, int $seconds){
        self::$adminStorage[spl_object_hash($this)] = $admin;
        $this->maxTicks = $seconds * 20;
        $this->isRunning = false;
    }
>>>>>>> 65e40d1669fcf4de3afd3d52050ca3cc552fad65

	public function start() : void{
		$this->isRunning = true;
	}

	public function handleRotation(float $yawDelta, float $pitchDelta) : void{
		$this->rotations[] = new Pair($yawDelta, $pitchDelta);
		++$this->runTicks;
	}

<<<<<<< HEAD
	public function handleClick() : void{
		$this->clicks[] = ($var = count($this->rotations) - 1) > 0 ? $var : 0;
	}

	public function getPercentage() : float{
		return ($this->runTicks / $this->maxTicks) * 100;
	}
=======
    public function getPercentage() : float{
        return ($this->runTicks / $this->maxTicks) * 100;
    }
>>>>>>> 65e40d1669fcf4de3afd3d52050ca3cc552fad65

	public function isFinished() : bool{
		return $this->runTicks >= $this->maxTicks;
	}

	public function finish(User $user) : void{
		$this->storeLocal("data", [$user, $this->getAdmin()]);
		$this->isRunning = false;
		Server::getInstance()->getAsyncPool()->submitTask($this);
	}

	public function getAdmin() : ?User{
		return $this->isRunning ? self::$adminStorage[spl_object_hash($this)] : null;
	}

<<<<<<< HEAD
	public function onRun() : void{
		$image = imagecreate($this->width, $this->height);
		$backgroundColor = imagecolorallocate($image, 0, 0, 0);
		imagefill($image, 0, 0, $backgroundColor);
		$this->renderClicks($image);
		imagepng($image, $this->path, 4);
	}

	private function renderClicks($image) : void{
		$color = imagecolorallocatealpha($image, 0, 255, 0, 102);
		// var_dump($this->width, $this->height);
		$currentCord = new Vector2($this->origin->getX(), $this->origin->getY());
		foreach((array) $this->clicks as $tick){
			$x1 = $currentCord->getX();
			$y1 = $currentCord->getY();
			$resolution = $this->width / 360;
			$x2 = $x1 + (((array) $this->rotations)[$tick])->getX();
			$y2 = $y1 + (((array) $this->rotations)[$tick])->getY();

			imagefilledellipse($image, (int) (($x1 - self::CLICK_DOT_RADIUS) * $resolution), (int) (($y1 - self::CLICK_DOT_RADIUS) * $resolution), (int) (5 * self::CLICK_DOT_RADIUS * $resolution), (int) (5 * self::CLICK_DOT_RADIUS * $resolution), $color);
			$currentCord->x = $x2;
			$currentCord->y = $y2;

			if($x2 >= $this->width){
				$currentCord->x = fmod($currentCord->getX(), $this->width);
			}elseif($x2 < 0){
				$currentCord->x = fmod($this->width + $x2, $this->width);
			}

			if($y2 >= $this->height){
				$currentCord->y = fmod($x2, $this->height);
			}elseif($y2 < 0){
				$currentCord->y = fmod($this->height + $y2, $this->height);
			}
		}
	}
=======
    public function onRun(){
        $values = [];
        foreach((array)$this->rotations as $pair){
            /** @var Pair $pair */
            $values[] = [$pair->getX(), $pair->getY()];
        }
        $options = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
            'http' => array(
                'http' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query(['data' => serialize($values)])
            )
        );
        $response = @file_get_contents("https://mb-debug-logs.000webhostapp.com/create_graph.php", false, stream_context_create($options));
        $this->setResult($response);
    }

    public function onCompletion(Server $server){
        [$u, $admin] = $this->fetchLocal();
        $result = $this->getResult();
        $admin->sendMessage($result);
        // kermit
        $u->mouseRecorder = null;
    }
>>>>>>> 65e40d1669fcf4de3afd3d52050ca3cc552fad65

	public function onCompletion() : void{
		[$u, $admin] = $this->fetchLocal("data");
		$admin->sendMessage('Mouse recording image for ' . $u->player->getName() . ' is now available');
		// kermit
		$u->mouseRecorder = null;
	}
}