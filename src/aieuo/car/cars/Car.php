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

use pocketmine\block\Block;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\SignPost;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;

class Car extends Vehicle {
	const NETWORK_ID = 84;

	protected $gravity = 0.1;

	public $height = 0.7;
	public $width = 0.98;
	public $brake = true;
	public $jump = true;

	private $player = null;

	private $speed = 0;
	private $max_speed = 0.5;
	private $accel = 0.004;

	public function setMaxSpeed(float $speed) {
		$this->max_speed = $speed;
	}

	public function setAccel(float $accel) {
		$this->accel = $accel;
	}

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
			if(!$this->player->isOnline()) {
				$this->onLeave();
				return false;
			}
			if(abs($this->x - $this->player->x) > 50 or abs($this->z - $this->player->z) > 50) {
				$this->onLeave();
				return false;
			}
			$this->yaw = $this->player->yaw + 90;
			$motion = new Vector3(
				(-sin($this->player->yaw / 180 * M_PI) * ($this->brake ? cos($this->player->pitch / 180 * M_PI) : 1) * $this->getSpeed() * $diff),
				0,
				(cos($this->player->yaw / 180 * M_PI) * ($this->brake ? cos($this->player->pitch / 180 * M_PI) : 1) * $this->getSpeed() * $diff)
			);
			$this->setMotion($motion);
			if($this->jump) {
				$this->jump();
		    }
		} elseif($this->hasMovementUpdate()) {
			$this->speed *= 0.999;
			$this->motion->x *= 0.999;
			$this->motion->z *= 0.999;
		}
		return parent::entityBaseTick($diff);
	}

	public function jump() {
		if(!($this->player instanceof Player) or !$this->player->isOnline()) return false;
		switch ($this->player->getDirection()) {
			case 0:
				$pos = $this->add(1);
				break;
			case 1:
				$pos = $this->add(0, 0, 1);
				break;
			case 2:
				$pos = $this->add(-1);
				break;
			case 3:
				$pos = $this->add(0, 0, -1);
				break;
		}
		if($this->level->getBlock($pos->add(0, 1))->getId() !== 0) return false;
		$block = $this->level->getBlock($pos);
		if($block->getId() === 0) return false;
		if(
			$block instanceof SignPost
			or $block instanceof Fence
			or $block instanceof FenceGate
		) {
			return false;
		}
        if($block instanceof Slab or $block instanceof Stair) {
            $this->motion->y = 0.2;
        }else{
            $this->motion->y = 0.4;
        }
		return true;
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
