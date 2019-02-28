<?php

namespace aieuo\car;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

use aieuo\car\cars\Car;

class Main extends PluginBase implements Listener {

	private $cars = [];

	public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		Entity::registerEntity(Car::class, false, ["Car"]);
	}

	public function onTouch(PlayerInteractEvent $event) {
		if($event->getItem()->getId() == Item::MINECART) {
			$nbt = Entity::createBaseNBT($event->getBlock()->getSide($event->getFace()));
			$entity = Entity::createEntity("Car", $event->getPlayer()->level, $nbt);
			$entity->spawnToAll();
		}
	}

	public function onRecive(DataPacketReceiveEvent $event) {
		$pk = $event->getPacket();
		if($pk instanceof InventoryTransactionPacket) {
			if($pk->transactionType !== InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) return;
			if($pk->trData->actionType !== InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT) return;

			$player = $event->getPlayer();
			$entity = $player->level->getEntity($pk->trData->entityRuntimeId);
			if(!($entity instanceof Car)) return;

			if(isset($this->cars[$player->getName()])) {
				$this->cars[$player->getName()]->onLeave();
			}
			$this->cars[$player->getName()] = $entity;
			$entity->onRide($player);
		}
	}

	public function onJump(PlayerJumpEvent $event) {
		$player = $event->getPlayer();
		if(isset($this->cars[$player->getName()])) {
			$this->cars[$player->getName()]->onLeave();
			unset($this->cars[$player->getName()]);
		}
	}
}