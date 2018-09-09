<?php

namespace AndreasHGK\BankNotes;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\nbt\NBT;
use pocketmine\inventory\Inventory;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\TextFormat as C;

class Main extends PluginBase implements Listener{
	
	public $NoteVersion = 2.0;
	private $cfg;

	public function onEnable() : void{
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->cfg = $this->getConfig()->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onDisable() : void{
        $this->config->save();
    }
	
	public function replaceVars($str, array $vars) : string{
        foreach($vars as $key => $value){
            $str = str_replace("{" . $key . "}", $value, $str);
        }
        return $str;
    }
	
	public function getNameConfig(){
		return TextFormat::colorize($this->replaceVars($this->cfg["note-name"], array(
			"VALUE" => $amount,
			"CREATOR" => $player)));
	}
	
	public function getLoreConfig(){
		return TextFormat::colorize($this->replaceVars($this->cfg["note-lore"], array(
			"VALUE" => $amount,
			"CREATOR" => $player)));
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
			
			return true;
			break;
			
			case "note":
			case "withdraw":
				
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
					if($amount > 0){
					#make and give the custom bank note and reduce playermoney
					EconomyAPI::getInstance()->reduceMoney($player, $amount);
					$note = Item::get(339, 0, 1);
					$note->setCustomName($this->getNameConfig());
					$note->setLore($this->getLoreConfig());
					$nbt = $note->getNamedTag();
					$nbt->setTag(new ByteTag("IsValidNote", true));
					$nbt->setTag(new IntTag("NoteVersion", $this->NoteVersion));
					$nbt->setTag(new IntTag("NoteValue", $amount));
					$nbt->setTag(new StringTag("Creator", $player));
					$sender->getInventory()->addItem($note);
					$sender->sendMessage(C::GREEN.C::BOLD."Success! ".C::RESET.C::GRAY."a $".$amount." note was given.");
					return true;
					} else {
						$sender->sendMessage(C::RED.C::BOLD."Error! ".C::RESET.C::GRAY."please provide a number greater than 0.");
						return true;
					}
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
			if ($nbt->hasTag("IsValidNote", ByteTag::class) && $nbt->getTagValue("IsValidNote") == true) {
			if($nbt->hasTag("NoteVersion", IntTag::class) && $nbt->getTagValue("NoteVersion") == $this->NoteVersion){
				$dep = $nbt->getTagValue("NoteValue");
				EconomyAPI::getInstance()->addMoney($player, $dep);
				$hand->setCount($hand->getCount() - 1);
				$inv->setItemInHand($hand);
				$sender->sendMessage(C::GREEN.C::BOLD."Success! ".C::RESET.C::GRAY."you deposited $".$dep." to your account.");
				return true;
			} else {
				$p->sendMessage(C::RED.C::BOLD."Error: ".C::RESET.C::GRAY."this is an outdated note.");
				return true;
			}
			}else{
			$p->sendMessage(C::RED.C::BOLD."Error: ".C::RESET.C::GRAY."this note is no longer valid.");
			return true;
			}
			break;
			
			default:
			return false;
		} return false;
	}
	
	#Thanks to JackMD for providing help with this part!
 	public function onInteract(PlayerInteractEvent $event) : void{
		$p = $event->getPlayer();
		$name = $p->getName();
		$inv = $p->getInventory();
		$hand = $inv->getItemInHand();
		$nbt = $hand->getNamedTag();
		if ($nbt->hasTag("IsValidNote", ByteTag::class) && $nbt->getTagValue("IsValidNote") == true) {
			if($nbt->hasTag("NoteVersion", IntTag::class) && $nbt->getTagValue("NoteVersion") == $this->NoteVersion){
				#check to see on which block the player claims the note
				switch($event->getBlock()->getName()){
					
				case "Item Frame":
				case "Anvil":
				case "Crafting Table":
				case "Furnace":
				case "Chest":
				case "Brewing Stand":
				case "Cake":
				case "Door":
				case "Wooden Door":
				case "Wooden Button":
				case "Stone Button":
				case "Enchanting Table":
				case "Ender Chest":
				case "Fence Gate":
				case "Iron Door":
				case "Stonecutter":
				case "Trapped Chest":
				case "Wooden Trapdoor":
				case "Bed":
					break;
					
				default:
				$dep = $nbt->getTagValue("NoteValue");
				EconomyAPI::getInstance()->addMoney($name, $dep);
				$hand->setCount($hand->getCount() - 1);
				$inv->setItemInHand($hand);
				$p->sendMessage(C::GREEN.C::BOLD."Success! ".C::RESET.C::GRAY."you deposited $".$dep." to your account.");
				break;
				}
				return;
			}else{
				$p->sendMessage(C::RED.C::BOLD."Error: ".C::RESET.C::GRAY."this is an outdated note.");
			}
		}
	}
}