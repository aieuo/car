<?php

namespace aieuo\car;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

use aieuo\car\cars\Car;

class Main extends PluginBase implements Listener {

	private $cars = [];

	public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, [
        	"maxspeed" => 0.5,
        	"accel" => 0.004,
        	"brake" => true,
        	"段差を超える" => true
        ]);
        $this->config->save();
		Entity::registerEntity(Car::class, false, ["Car"]);
	}

	public function onTouch(PlayerInteractEvent $event) {
		if($event->getItem()->getId() == Item::MINECART) {
			$level = $event->getPlayer()->getLevel();
			$pos = $event->getBlock()->getSide($event->getFace())->asVector3();
			if(!$level->isChunkLoaded($pos->x, $pos->y)) $level->loadChunk($pos->x, $pos->y);

			$nbt = Entity::createBaseNBT($pos);
			$entity = Entity::createEntity("Car", $event->getPlayer()->level, $nbt);
			$entity->spawnToAll();
			$entity->setMaxSpeed((float)$this->config->get("maxspeed"));
			$entity->setAccel((float)$this->config->get("accel"));
			$entity->brake = (boolean)$this->config->get("brake");
			$entity->jump = (boolean)$this->config->get("段差を超える");
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
		} elseif($pk instanceof InteractPacket) {
			if($pk->action == InteractPacket::ACTION_LEAVE_VEHICLE) {
				$player = $event->getPlayer();
				$this->checkLeaveCar($player);
			}
		}
	}

	public function checkLeaveCar($player) {
		if(isset($this->cars[$player->getName()])) {
			$this->cars[$player->getName()]->onLeave();
			unset($this->cars[$player->getName()]);
		}
	}

	public function onDeath(PlayerDeathEvent $event) {
		$player = $event->getPlayer();
		$this->checkLeaveCar($player);
	}

	public function onTeleport(EntityTeleportEvent $event) {
		$player = $event->getEntity();
		if($player instanceof Player) $this->checkLeaveCar($player);
	}

	public function onLevelChange(EntityLevelChangeEvent $event) {
		$player = $event->getEntity();
		if($player instanceof Player) $this->checkLeaveCar($player);
	}
}