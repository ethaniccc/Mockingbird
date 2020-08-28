<?php


namespace ethaniccc\Mockingbird\utils\user;


use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\InputMode;

final class ClientData{

	protected $clientData;
	
	public function __construct(array $clientData){
		$this->clientData = $clientData;
	}

	public function getClientData() : array{
		return $this->clientData;
	}

	public function getCurrentInputMode() : ?int{
		return $this->getClientData()['CurrentInputMode'] ?? null;
	}

	public function getDefaultInputMode() : ?int{
		return $this->getClientData()['DefaultInputMode'] ?? null;
	}

	public function getDeviceModel() : ?string{
		return $this->getClientData()['DeviceModel'] ?? null;
	}
	
	public function getDeviceOS() : ?int{
		return $this->getClientData()['DeviceOS'] ?? null;
	}
	
	public function getGameVersion() : ?string{
		return $this->getClientData()['GameVersion'] ?? null;
	}
	
	public function getGuiScale() : ?int{
		return $this->getClientData()['GuiScale'] ?? null;
	}
	
	public function getLanguageCode() : ?string{
		return $this->getClientData()['LanguageCode'] ?? null;
	}

	public function getThirdPartyName() : ?string{
		return $this->getClientData()['ThirdPartyName'];
	}
	
	public function getThirdPartyNameOnly() : ?string{
		return $this->getClientData()['ThirdPartyNameOnly'];
	}
	
	public function getUIProfile() : ?int{
		return $this->getClientData()['UIProfile'];
	}
	
	public function isMobile() : bool{
		$deviceOS = $this->getDeviceOS();
		return in_array($deviceOS, [DeviceOS::AMAZON, DeviceOS::ANDROID, DeviceOS::IOS]);
	}
	
	public function isController() : bool {
		return $this->getCurrentInputMode() === InputMode::GAME_PAD;
	}
	
	public function isMotionController() : bool {
		return $this->getCurrentInputMode() === InputMode::MOTION_CONTROLLER;
	}
	
	public function isDesktop() : bool {
		return !$this->isMobile();
	}
	
	public function usesKeyboard() : bool {
		return $this->getCurrentInputMode() === InputMode::MOUSE_KEYBOARD;
	}
	
	public function isTouchScreen() : bool {
		return $this->getCurrentInputMode() === InputMode::TOUCHSCREEN;
	}
}