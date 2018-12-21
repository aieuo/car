<?php

namespace aieuo\car;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerInteractEvent;
use aieuo\car\cars\Car;

class Main extends PluginBase implements Listener
{
	public function onEnable()
	{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		Entity::registerEntity(Car::class, false, ["Car"]);
	}

	public function onTouch(PlayerInteractEvent $event)
	{
		if($event->getItem()->getId() == Item::MINECART)
		{
			$nbt = Entity::createBaseNBT($event->getBlock()->getSide($event->getFace()));
			$entity = Entity::createEntity("Car", $event->getPlayer()->level, $nbt);
			$entity->spawnToAll();
		}
	}
}