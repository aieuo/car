<?php

namespace aieuo\car;

use pocketmine\level\Position;
use pocketmine\Player;
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

use aieuo\car\cars\Dolphin;
use aieuo\car\cars\Car;

class Main extends PluginBase implements Listener {

	private $cars = [];
    /* @var Config */
    private $setting;

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->setting = new Config($this->getDataFolder()."config.yml", Config::YAML, [
        	"maxspeed" => 0.5,
        	"accel" => 0.004,
        	"brake" => true,
        	"段差を超える" => true
        ]);
        $this->setting->save();
		Entity::registerEntity(Car::class, true, ["Car"]);
		Entity::registerEntity(Dolphin::class, true, ["DolphinCar"]);
	}

	public function onTouch(PlayerInteractEvent $event) {
		$item = $event->getItem();
		if($item->getId() == Item::MINECART) {
			$pos = $event->getBlock()->getSide($event->getFace());
			$this->spawnCar($pos, "Car");
		} elseif($item->getId() == Item::SPAWN_EGG and $item->getDamage() == Entity::DOLPHIN) {
			$pos = $event->getBlock()->getSide($event->getFace());
			$this->spawnCar($pos, "DolphinCar");
		}
	}

	public function spawnCar(Position $pos, string $entityName) {
		if(!$pos->level->isChunkLoaded($pos->x >> 4, $pos->y >> 4)) $pos->level->loadChunk($pos->x >> 4, $pos->y >> 4);
			$nbt = Entity::createBaseNBT($pos);
		    $entity = Entity::createEntity($entityName, $pos->level, $nbt);
			$entity->spawnToAll();
			$entity->setMaxSpeed((float)$this->setting->get("maxspeed"));
			$entity->setAccel((float)$this->setting->get("accel"));
			$entity->brake = (boolean)$this->setting->get("brake");
			$entity->jump = (boolean)$this->setting->get("段差を超える");
		}

	public function onReceive(DataPacketReceiveEvent $event) {
		$pk = $event->getPacket();
		if($pk instanceof InventoryTransactionPacket) {
			if($pk->transactionType !== InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) return;
			if($pk->trData->actionType !== InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT) return;

			$player = $event->getPlayer();
			$entity = $player->level->getEntity($pk->trData->entityRuntimeId);
			if(!($entity instanceof Car) and !($entity instanceof Dolphin)) return;

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

	public function checkLeaveCar(Player $player) {
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