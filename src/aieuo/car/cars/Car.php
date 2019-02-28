<?php

namespace aieuo\car\cars;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\entity\Vehicle;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\event\entity\EntityDamageEvent;

class Car extends Vehicle {
	const NETWORK_ID = 84;

	protected $gravity = 0.1;

	public $height = 0.7;
	public $width = 0.98;

	private $player = null;

	private $speed = 0;
	private $max_speed = 0.5;
	private $accel = 0.004;

	public function onLeave() {
		$this->player = null;
	}

	public function onRide($rider) {
		$this->player = $rider;

		$rider->getDataPropertyManager()->setVector3(self::DATA_RIDER_SEAT_POSITION, new Vector3(0, 1, 0));

		$pk = new SetEntityLinkPacket();
		$pk->link = new EntityLink($this->id, $rider->getId(), EntityLink::TYPE_RIDER);
		$rider->getServer()->broadcastPacket($rider->level->getPlayers(), $pk, true);
	}

	public function entityBaseTick(int $diff = 1) : bool {
		if($this->player instanceof Player) {
			if(!$this->player->isOnline() or !$this->player->isAlive()) {
				$this->onLeave();
				return false;
			}
			if(abs($this->x - $this->player->x) > 3 or abs($this->z - $this->player->z) > 3) {
				$this->onLeave();
				return false;
			}
			$this->yaw = $this->player->yaw + 90;
			$motion = new Vector3(
				(-sin($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI) * $this->getSpeed() * $diff),
				0,
				(cos($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI) * $this->getSpeed() * $diff)
			);
			$this->setMotion($motion);
			$this->y ++;
		} elseif($this->hasMovementUpdate()) {
			$this->speed *= 0.999;
			$this->motion->x *= 0.999;
			$this->motion->z *= 0.999;
		}
		return parent::entityBaseTick($diff);
	}

	public function getSpeed() {
		$this->speed += $this->accel;
		if($this->speed > $this->max_speed) $this->speed = $this->max_speed;
		return $this->speed;
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);

		if($source->isCancelled()) return;
		$this->kill();
	}
}
