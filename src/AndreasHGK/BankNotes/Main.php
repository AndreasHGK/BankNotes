<?php

namespace AndreasHGK\BankNotes;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\inventory\Inventory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\TextFormat as C;

class Main extends PluginBase{

	public function onEnable(){
		$this->getLogger()->info("enabled!");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$player = $sender->getName();
		#check if command is ran from console
		if(!($sender instanceof Player)){
			$sender->sendMessage(C::RED."Please run this command in-game");
			return true;
		}
		switch(strtolower($command->getName())){
			
			case "banknotes":
			case "banknote":
			case "note":
				
				#check if player used arguments
				if(empty($args[0])){
					$sender->sendMessage(C::RED.C::BOLD."Usage: ".C::RESET.C::GRAY."/note {amount}");
				return true;
			} else{
				
				#check if argument is integer
				if(!is_int($args[0])){
					$amount = (int)$args[0];
				}
				if(is_int($amount)){
					$bal = EconomyAPI::getInstance()->myMoney($player);
				if($bal >= $amount) {
					#make and give the custom bank note and reduce playermoney
					EconomyAPI::getInstance()->reduceMoney($player, $amount);
					$note = Item::get(339, 0, 1);
					$note->setCustomName(C::RESET.C::YELLOW."$".C::GOLD.$amount.C::YELLOW." note");
					$note->setLore([
					C::RESET.C::DARK_RED."Right-Click ".C::RED."to claim this note",
					C::RESET.C::RED.$amount
					]);
					$sender->getInventory()->addItem($note);
					$sender->sendMessage(C::GREEN.C::BOLD."Success! ".C::RESET.C::GRAY."a $".$amount." note was given.");
					return true;
				} else {
					$sender->sendMessage(C::RED.C::BOLD."Error! ".C::RESET.C::GRAY."player has insufficient money.");
					return true;
				}
				} else{
					$sender->sendMessage(C::RED.C::BOLD."Error! ".C::RESET.C::GRAY."please enter an integer.");
					return true;
				}
			}
			break;
			
			case "deposit":
			$inv = $sender->getInventory();
			$hand = $inv->getItemInHand();
			$lore = $hand->getlore();
/* 			$sender->sendMessage(C::YELLOW.C::BOLD."Debug: ".C::RESET.C::GRAY."HAND: ".$hand);
			$sender->sendMessage(C::YELLOW.C::BOLD."Debug: ".C::RESET.C::GRAY."HANDCNAME: ".$hand->getCustomName());
			$sender->sendMessage(C::YELLOW.C::BOLD."Debug: ".C::RESET.C::GRAY."HANDLORE0: ".$lore[0]);
			$sender->sendMessage(C::YELLOW.C::BOLD."Debug: ".C::RESET.C::GRAY."HANDLORE1: ".$lore[1]); */
			if (!empty($lore)) {
			if(C::clean($lore[0]) == 'Right-Click to claim this note'){
				$dep = (int)C::clean($lore[1]);
				#$sender->sendMessage(C::YELLOW.C::BOLD."Debug: ".C::RESET.C::GRAY."DEPOSIT: ".$dep);
				EconomyAPI::getInstance()->addMoney($player, $dep);
				$hand->setCount($hand->getCount() - 1);
				$inv->setItemInHand($hand);
				$sender->sendMessage(C::GREEN.C::BOLD."Success! ".C::RESET.C::GRAY."you deposited $".$dep." to your account.");
				return true;
			} else {
				$sender->sendMessage(C::RED.C::BOLD."Error! ".C::RESET.C::GRAY."you must be holding a bank note.");
				return true;
			}
			} else {
				$sender->sendMessage(C::RED.C::BOLD."Error! ".C::RESET.C::GRAY."you must be holding a bank note.");
				return true;
			}
			break;
			
			default:
			return false;
	}
	}
	
/* 	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
		if(($item->getId() == 339){//339 is paper
			#add money to player
			$item->setCount($item->getCount() - 1);
			$player->getInventory()->setItemInHand($item);
		}
	} */
	
	public function onDisable(){
		$this->getLogger()->info("disabled!");
	}
}