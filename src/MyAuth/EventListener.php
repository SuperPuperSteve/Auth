<?php

namespace MyAuth;

use MyAuth\MyAuth;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

class EventListener implements Listener {
	
	public function __construct(MyAuth $plugin){
		$this->plugin = $plugin;
		$this->lang = $this->plugin->getLanguage();
	}
	
	public function onPlayerLogin(PlayerJoinEvent $event){
		$database = $this->plugin->getDatabase();
		$player = $event->getPlayer();
		
		$data = $database->getPlayerData($player);
		
		/* если не зарегестрирован */
		if($data == null){
			$player->sendMessage($this->lang->getMessage('register'));
			return false;
		} 

		/* в противном случае пытаемся авторизировать автоматически */
		if(($data['ip'] == $player->getAddress()) && ($data['cid'] == $player->getClientId()))
		{
			$this->plugin->authorize($player);
			$player->sendMessage($this->lang->getMessage('login_auto'));
			return true;
		}
		
		$player->sendMessage($this->lang->getMessage('login'));
		
	} /* конец */
	
	public function onQuit(PlayerQuitEvent $event){
		$this->plugin->deauthorize($event->getPlayer());
	}
	
	public function onChat(PlayerCommandPreprocessEvent $event){
		/* если игрок не авторизован */
		if(!($this->plugin->isAuthorized($event->getPlayer()))){
			$command = explode(' ', $event->getMessage());
			$allowed = ['/login', '/l', '/register', '/reg'];
			
			if(!in_array($command[0], $allowed)) $event->setCancelled();
		}
	}
	
	public function onBreak(BlockBreakEvent $event){
		if(!$this->plugin->isAuthorized($event->getPlayer())) $event->setCancelled();
	}
	
	public function onPlace(BlockPlaceEvent $event){
		if(!$this->plugin->isAuthorized($event->getPlayer())) $event->setCancelled();
	}
	
	public function onDrop(PlayerDropItemEvent $event){
		if(!$this->plugin->isAuthorized($event->getPlayer())) $event->setCancelled();
	}
	
	public function onInteract(PlayerInteractEvent $event){
		if(!$this->plugin->isAuthorized($event->getPlayer())) $event->setCancelled();
	}

}