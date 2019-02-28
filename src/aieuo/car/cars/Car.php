<?php

namespace aieuo\car\cars;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\entity\Vehicle;

class Car extends Vehicle
{
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

	public function onRiderMount(Entity $rider) : void
	{
		$this->player = $rider;
	}

	public function entityBaseTick(int $diff = 1) : bool
	{
		if($this->player instanceof Player)
		{
			if(!$this->player->isOnline() or !$this->player->isAlive())
			{
				$this->player = null;
				$this->motionX = 0;
				$this->motionY = 0;
				$this->motionZ = 0;
				$this->speed = 0;
				return false;
			}
			$this->yaw = $this->player->yaw + 90;
			$motion = new Vector3(
				(-sin($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI) * $this->getSpeed() * $diff),
				0,
				(cos($this->player->yaw / 180 * M_PI) * cos($this->player->pitch / 180 * M_PI) * $this->getSpeed() * $diff)
			);
			$this->setMotion($motion);
		}
		elseif($this->hasMovementUpdate())
		{
			$this->motion->x *= 0.85;
			$this->motion->z *= 0.85;
			$this->speed *= 0.85;
		}
		return parent::entityBaseTick($diff);
	}

	public function getSpeed()
	{
		$this->speed += $this->accel;
		if($this->speed > $this->max_speed) $this->speed = $this->max_speed;
		return $this->speed;
	}

	}
}
