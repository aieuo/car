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

class Car extends Vehicle {
	const NETWORK_ID = self::MINECART;

	public $height = 0.8;
	public $width = 0.98;

	protected $rotationAdd = 90;

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
			or $block instanceof Liquid
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

	public function getDrops() : array{
		$drops = [
			Item::get(Item::MINECART, 0, 1)
		];
		return $drops;
	}
}
