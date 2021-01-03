<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\player\Player;

class PlayerInventory extends BaseInventory{

	/** @var Human */
	protected $holder;

	/** @var int */
	protected $itemInHandIndex = 0;

	public function __construct(Human $player){
		$this->holder = $player;
		parent::__construct(36);
	}

	public function isHotbarSlot(int $slot) : bool{
		return $slot >= 0 and $slot <= $this->getHotbarSize();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function throwIfNotHotbarSlot(int $slot) : void{
		if(!$this->isHotbarSlot($slot)){
			throw new \InvalidArgumentException("$slot is not a valid hotbar slot index (expected 0 - " . ($this->getHotbarSize() - 1) . ")");
		}
	}

	/**
	 * Returns the item in the specified hotbar slot.
	 *
	 * @throws \InvalidArgumentException if the hotbar slot index is out of range
	 */
	public function getHotbarSlotItem(int $hotbarSlot) : Item{
		$this->throwIfNotHotbarSlot($hotbarSlot);
		return $this->getItem($hotbarSlot);
	}

	/**
	 * Returns the hotbar slot number the holder is currently holding.
	 */
	public function getHeldItemIndex() : int{
		return $this->itemInHandIndex;
	}

	/**
	 * Sets which hotbar slot the player is currently loading.
	 *
	 * @param int  $hotbarSlot 0-8 index of the hotbar slot to hold
	 * @param bool $send Whether to send updates back to the inventory holder. This should usually be true for plugin calls.
	 *                    It should only be false to prevent feedback loops of equipment packets between client and server.
	 *
	 * @throws \InvalidArgumentException if the hotbar slot is out of range
	 */
	public function setHeldItemIndex(int $hotbarSlot, bool $send = true) : void{
		$this->throwIfNotHotbarSlot($hotbarSlot);

		$this->itemInHandIndex = $hotbarSlot;

		if($this->holder instanceof Player and $send){
		    if(($session = $this->holder->getNetworkSession())->isConnected()){
                $session->getInvManager()->syncSelectedHotbarSlot();
            }
		}
		foreach($this->holder->getViewers() as $viewer){
            if(($session = $viewer->getNetworkSession())->isConnected()){
                $session->onMobEquipmentChange($this->holder);
            }
		}
	}

	/**
	 * Returns the currently-held item.
	 */
	public function getItemInHand() : Item{
		return $this->getHotbarSlotItem($this->itemInHandIndex);
	}

	/**
	 * Sets the item in the currently-held slot to the specified item.
	 */
	public function setItemInHand(Item $item) : void{
		$this->setItem($this->getHeldItemIndex(), $item);
	}

    protected function onSlotChange(int $index, Item $before) : void
    {
        foreach($this->listeners as $listener){
            $listener->onSlotChange($this, $index, $before);
        }

        foreach($this->viewers as $viewer){
            if(($session = $viewer->getNetworkSession())->isConnected()) {
                $session->getInvManager()->syncSlot($this, $index);
            }
        }

        if($index === $this->getHeldItemIndex()){
            foreach($this->holder->getViewers() as $viewer){
                if(($session = $viewer->getNetworkSession())->isConnected()){
                    $session->onMobEquipmentChange($this->holder);
                }
            }
        }
    }

	/**
	 * Returns the number of slots in the hotbar.
	 */
	public function getHotbarSize() : int{
		return 9;
	}

	/**
	 * @return Human|Player
	 */
	public function getHolder(){
		return $this->holder;
	}
}
