<?php

namespace aieuo\car\cars;

use pocketmine\Player;
use pocketmine\item\Item;

use pocketmine\block\Block;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\SignPost;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\Liquid;
use pocketmine\block\Water;
use pocketmine\block\AIR;

class Dolphin extends Vehicle {
    const NETWORK_ID = self::DOLPHIN;

    public $height = 0.7;
    public $width = 1.6;

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
            $this->setRotation($this->player->yaw, 0);
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
        $block = $this->level->getBlock($pos->add(0, 1));
        if(!($block instanceof AIR) and !($block instanceof Liquid)) return false;
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
            $this->motion->y = 1;
        }else{
            $this->motion->y = 2;
        }
        return true;
    }
}
