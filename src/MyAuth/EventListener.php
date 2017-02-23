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
		$db = $this->plugin->getDB();
		$player = $event->getPlayer();
		$nickname = strtolower($player->getName());
		
		$info = $db->query("SELECT * FROM `{$this->plugin->config->get('table_prefix')}pass` WHERE nickname='$nickname';");
		
		/* если не зарегестрирован */
		if($info->num_rows == 0){
			$player->sendMessage($this->lang->getMessage('register'));
		} else {
			$data = $info->fetch_assoc();
			/* в противном случае пытаемся авторизировать автоматически */
			if(($data['ip'] == $player->getAddress()) && ($data['cid'] == $player->getClientId()))
			{
				$this->plugin->authorize($player);
				$player->sendMessage($this->lang->getMessage('login_auto'));
			} else 
			{
				/* не удалось авторизировать автоматически, обычная авторизация */
				$player->sendMessage($this->lang->getMessage('login'));
			}
		} /* конец */
	}
	
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