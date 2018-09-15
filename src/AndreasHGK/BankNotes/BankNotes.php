<?php

namespace AndreasHGK\BankNotes;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\Player;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\TextFormat as C;

class BankNotes extends PluginBase implements Listener{
	
	public $NoteVersion = 2.1;
	public $cfg;
	public $CompalibleVersions = [2.0, 2.1];
	
	
	/**
	* @return BankNotes $BankNotes
	*/
	public static function getInstance(){
		return $this;
	}
	
	/**
	* @return int $version
	*/
	public function getVersion() : int{
		return $this->getDescription()->getVersion();
	}
	
	/**
	* @return int $noteVersion
	*/
	public function getNoteVersion() : int{
		return $this->NoteVersion;
	}
	
	/**
	* @return int[] $compatibleVersions
	*/
	public function getCompatibleVersions() : array{
		return $this->CompalibleVersions;
	}
	
	/**
	* @param item $note
	* @param player $p
	*
	* @return bool $success
	*/
	public function devalidate(item $note, player $p) : bool{
		if($this->checkValidity($note)){
			$nbt = $note->getNamedTag();
			$nbt->setByte("IsValidNote", false, true);
			$note->setCompoundTag($nbt);
			$p->getInventory()->setItemInHand($note);
			return true;
		}else{
			return false;
		}
	}

	/**
	* @param item $note
	* @param player $p
	*
	* @return bool $success
	*/
	public function validate(item $note, player $p) : bool{
		if($this->checkDevalidated($note)){
			$nbt = $note->getNamedTag();
			$nbt->setByte("IsValidNote", true, true);
			$note->setCompoundTag($nbt);
			$p->getInventory()->setItemInHand($note);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	* @param item $note
	*
	* @return bool $valid
	*/
	public function checkValidity(item $note) : bool{
		$nbt = $note->getNamedTag();
		if($nbt->getByte("IsValidNote", false) == true && in_array($nbt->getInt("NoteVersion", 0), $this->getCompatibleVersions()) && $nbt->getInt("NoteValue", 0) > 0){
			if($nbt->hasTag("Econid", StringTag::class)){
				if($nbt->getString("Econid", NULL) == $this->cfg["economyid"]){
					return true;
				}else{
					return false;
				}
			}elseif($this->cfg["econid-compatibility"]){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
	* @param item $note
	*
	* @return bool $devalidated
	*/
	public function checkDevalidated(item $note) : bool{
		$nbt = $note->getNamedTag();
		if($nbt->getByte("IsValidNote", false) == false && in_array($nbt->getInt("NoteVersion", 0), $this->getCompatibleVersions()) && $nbt->getInt("NoteValue", 0) > 0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	* @param player $p
	* @param bool $interact
	* @param PlayerInteractEvent $event
	*
	* @return int $value
	*/
	public function depositCheck(player $p, bool $interact = false, PlayerInteractEvent $event = NULL) : int{

		$inv = $p->getInventory();
		$hand = $inv->getItemInHand();
		$nbt = $hand->getNamedTag();
		if ($nbt->getByte("IsValidNote", false) == true) {
			if(in_array($nbt->getInt("NoteVersion", 0), $this->getCompatibleVersions())){
				
				
				#check to see on which block the player claims the note if it's a playerinteractevent
				if($interact){
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
							return -3;
							break;
						
						default:
							if($nbt->hasTag("Econid", StringTag::class)){
								if($nbt->getString("Econid", NULL) == $this->cfg["economyid"]){
									return $this->deposit($p);
								}else{
									return -2;
								}
							}elseif($this->cfg["econid-compatibility"]){
								return $this->deposit($p);
							}else{
								return -2;
							}
							break;
					}
				}else{
					if($nbt->hasTag("Econid", StringTag::class)){
						if($nbt->getString("Econid", NULL) == $this->cfg["economyid"]){
							return $this->deposit($p);
						}else{
							return -2;
						}
					}elseif($this->cfg["econid-compatibility"]){
						return $this->deposit($p);
					}else{
						return -2;
					}
				}
			}else{
				return -1;
			}
		}else{
			return -2;
		}
	}
	
	/**
	* @param int $value
    * @param int $count
	* @param string $player
    *
	* @return item $note
	*/
	public function noteItem(int $value, int $count = 1, string $player = "ADMIN") : item{
		$note = Item::get($this->cfg["note-id"], 0, $count);
		$note->setCustomName(C::colorize($this->replaceVars($this->cfg["note-name"], array(
			"VALUE" => $amount,
			"USER" => $player))));
					
		$loreint = 0;
		$lorearray	;
		foreach($this->cfg["note-lore"] as $line){
			$lorearray[$loreint] = C::colorize($this->replaceVars($line, array("VALUE" => $value, "CREATOR" => $player)));
			$loreint++;
		}
					
		$note->setLore($lorearray);
		$nbt = $note->getNamedTag();
		$nbt->setByte("IsValidNote", true);
		$nbt->setInt("NoteVersion", $this->NoteVersion);
		$nbt->setInt("NoteValue", $value);
		$nbt->setString("Creator", $player);
		$nbt->setString("Econid", $this->cfg["economyid"]);
		$note->setCompoundTag($nbt);
		
		return $note;
	}
	
	
	## NON-API CODE
	

	public function onEnable() : void{
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->cfg = $this->getConfig()->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
	}
	
	public function replaceVars($str, array $vars) : string{
        foreach($vars as $key => $value){
            $str = str_replace("{" . $key . "}", $value, $str);
        }
        return $str;
    }

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$player = $sender->getName();
		#check if command is ran from console
		if(!($sender instanceof Player)){
			$sender->sendMessage(C::colorize($this->cfg["error-execute-ingame"]));
			return true;
		}
		switch(strtolower($command->getName())){
			
			#future command
 			case "banknotes":
				if($sender->hasPermission("banknotes.command")){
				if(empty($args[0])){
					$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["unknown-command"], array(
						"USER" => $player,
						"FULLNAME" => $this->getDescription()->getFullName()))));
					return true;
					break;
				}
				switch(strtolower($args[0])){
					case "admin":
					if($sender->hasPermission("banknotes.command.admin")){
					if(empty($args[1])){
					$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["unknown-command-admin"], array(
						"USER" => $player,
						"FULLNAME" => $this->getDescription()->getFullName()))));
					return true;
					break;
					}
					switch(strtolower($args[1])){
						
						case "reload":
							$this->reloadConfig();
							$this->cfg = $this->getConfig()->getAll();
							$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["reload-success"], array(
								"USER" => $player))));
						return true;
						
						case "devalidate":
							$devalidate = $this->devalidate($sender->getInventory()->getItemInHand(), $sender);
							if($devalidate){
								$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["devalidate-success"], array(
									"USER" => $player))));
							}else{
								$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["devalidate-error"], array(
									"USER" => $player))));
							}
						return true;
						break;
						
						case "validate":
							$validate = $this->validate($sender->getInventory()->getItemInHand(), $sender);
							if($validate){
								$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["validate-success"], array(
									"USER" => $player))));
							}else{
								$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["validate-error"], array(
									"USER" => $player))));
							}
						return true;
						break;
						
						default:
							$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["unknown-command-admin"], array(
								"USER" => $player,
								"FULLNAME" => $this->getDescription()->getFullName()))));
						return true;
						break;
					}
					}else{
						$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-nopermission"], array(
							"USER" => $player))));
						return true;
						break;
					}
					
					case "check":
						if($this->checkValidity($sender->getInventory()->getItemInHand())){
							$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["note-check-valid"], array(
								"USER" => $player))));
						}else{
							$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["note-check-invalid"], array(
								"USER" => $player))));
						}
					return true;
					
					default:
						$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["unknown-command"], array(
							"USER" => $player,
							"FULLNAME" => $this->getDescription()->getFullName()))));
					return true;
					break;
				}
				break;
				}else{
					$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-nopermission"], array(
						"USER" => $player))));
				return true;
				break;
				}
			
			
			case "note":
			case "withdraw":
				
				#check if player used arguments
				if(empty($args[0])){
					$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-empty-argument"], array(
						"USER" => $player))));
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
						
						if(!empty($args[1]) && is_int((int)$args[1])){
							$count = (int)$args[1];
						}else{
							$count = 1;
						}
					
					$reduce = $count * $amount;
					#make and give the custom bank note and reduce playermoney
					EconomyAPI::getInstance()->reduceMoney($player, $reduce);
					
					$note = $this->noteItem($amount, (int)$count, $player);
					
					$sender->getInventory()->addItem($note);
					$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["withdraw-sucess"], array(
						"VALUE" => $amount,
						"USER" => $player,
						"COUNT" => $count,
						"TOTAL_WITHDRAW" => $reduce))));
					return true;
					} else {
						$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-value-invalid"], array(
						"VALUE" => $amount,
						"USER" => $player))));
						return true;
					}
				} else {
					$playermoney = EconomyAPI::getInstance()->myMoney($player);
					$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-insufficient-money"], array(
						"VALUE" => $amount,
						"USER" => $player,
						"BAL" => $playermoney))));
					return true;
				}
				} else{
					$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-invalid-argument"], array(
						"VALUE" => $amount,
						"USER" => $player))));
					return true;
				}
			}
			break;
			
			case "deposit":
			$return = $this->depositCheck($sender);
			switch($return){
				case -1:
				$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-note-incompatible"], array(
					"USER" => $player))));
				return true;
				break;
			
				case -2:
				$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["error-note-invalid"], array(
					"USER" => $player))));
				return true;
				break;
				
				case -3;
				return true;
				break;

				default:
				$sender->sendMessage(C::colorize($this->replaceVars($this->cfg["deposit-succes"], array(
					"VALUE" => $return,
					"USER" => $player))));
				return true;
				break;
				
			}
		
		} return false;
	}
	
	#Thanks to JackMD for providing help with this part!
 	public function onInteract(PlayerInteractEvent $event) : void{
		$p = $event->getPlayer();
		$name = $p->getName();
		$return = $this->depositCheck($event->getPlayer(), true, $event);
		switch($return){
			case -1:
			$p->sendMessage(C::colorize($this->replaceVars($this->cfg["error-note-incompatible"], array(
				"USER" => $name))));
			break;

			case -2:
			break;
			
			case -3:
			break;
			
			default:
			$p->sendMessage(C::colorize($this->replaceVars($this->cfg["deposit-succes"], array(
				"VALUE" => $return,
				"USER" => $name))));
			break;
		}
	}
	
	public function deposit(player $p) : int{
		
		$name = $p->getName();
		$inv = $p->getInventory();
		$hand = $inv->getItemInHand();
		$nbt = $hand->getNamedTag();
		
		$dep = $nbt->getInt("NoteValue");
		$count = $hand->getCount();
		$money = $dep * (int)$count;
		EconomyAPI::getInstance()->addMoney($name, (int)$money);
		$hand->setCount($hand->getCount() - (int)$count);
		$inv->setItemInHand($hand);
		return $money;
	}
}