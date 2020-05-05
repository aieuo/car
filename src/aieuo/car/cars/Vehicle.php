<?php

namespace aieuo\car\cars;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\Vehicle as PMVehicle;

class Vehicle extends PMVehicle {
    public $gravity = 1.5;

    public $brake = true;
    public $jump = true;

    public $max_speed = 0.5;
    public $accel = 0.001;

    protected $speed = 0;

    protected $player = null;

    protected $rotationAdd = 0;

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
        if($this->player instanceof Player and $this->player->isOnline()) return false;
        $this->player = $rider;

        $rider->getDataPropertyManager()->setVector3(self::DATA_RIDER_SEAT_POSITION, new Vector3(0, 1, 0));

        $pk = new SetEntityLinkPacket();
        $pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_PASSENGER);
        $rider->getServer()->broadcastPacket($rider->getServer()->getOnlinePlayers(), $pk);
        return true;
    }

    public function onUpdate(int $currentTick): bool {
        parent::onUpdate($currentTick);
        if($this->player instanceof Player) {
            if(!$this->player->isOnline()) {
                $this->onLeave();
                return false;
            }
            if(abs($this->x - $this->player->x) > 50 or abs($this->z - $this->player->z) > 50) {
                $this->onLeave();
                return false;
            }
            $this->motion->x = (-sin($this->player->yaw / 180 * M_PI) * ($this->brake ? cos($this->player->pitch / 180 * M_PI) : 1) * $this->getSpeed());
            $this->motion->z = (cos($this->player->yaw / 180 * M_PI) * ($this->brake ? cos($this->player->pitch / 180 * M_PI) : 1) * $this->getSpeed());
            $this->setRotation($this->player->yaw + $this->rotationAdd, 0);
            $jump = false;
            if($this->jump) $jump = $this->jump();
            if(!$jump) $this->motion->y -= 0.03999999910593033;
        } elseif($this->hasMovementUpdate()) {
            $this->speed *= 0.999;
            $this->motion->x *= 0.999;
            $this->motion->z *= 0.999;
        }
        return !$this->onGround or abs($this->motion->x) > 0.00001 or abs($this->motion->y) > 0.00001 or abs($this->motion->z) > 0.00001;
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