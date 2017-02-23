<?php
namespace MyAuth\Database;

use MyAuth\Database\BaseDatabase;
use MyAuth\MyAuth;

use pocketmine\Player;

class MySQLDatabase implements BaseDatabase {
	
	private $cache;
		
	public function __construct(MyAuth $plugin, array $data){
		$this->plugin = $plugin;
		$this->lang = $this->plugin->getLanguage();
		$this->data = $data;
		$this->db_init();
		var_dump(1);
	}
	
	public function db_init(){
		$this->plugin->getLogger()->info($this->lang->getMessage('db_init'));
		
		$this->database = @new \mysqli($this->data['ip'], $this->data['username'], $this->data['password']);
		
		if($this->database->connect_errno){
			$this->plugin->getLogger()->info($this->lang->getMessage('db_conn_error', ['{error}'], [$this->database->connect_error]));
			return false;
		}
		
		$this->plugin->getLogger()->info($this->lang->getMessage('db_success'));
		
		$this->database->query("CREATE DATABASE IF NOT EXISTS {$this->plugin->config->get('database')}");
		$this->database->select_db($this->plugin->config->get('database'));
		$this->database->query("
					CREATE TABLE IF NOT EXISTS `{$this->plugin->config->get('table_prefix')}pass` (
						`nickname` varchar(16) NOT NULL,
						`firstlogin` bigint(20) NOT NULL,
						`lastlogin` bigint(20) NOT NULL,
						`password_hash` varchar(255) NOT NULL,
						`ip` varchar(16) NOT NULL,
						`cid` text NOT NULL,
						PRIMARY KEY (`nickname`)
					);
		");
	}
	
	public function authorizePlayer(Player $player, $ip, $loginTime, $cid){
		$nickname = strtolower($player->getName());
		
		$this->database->query("
		UPDATE `{$this->plugin->config->get('table_prefix')}pass`
		SET ip='$ip', lastlogin=$loginTime, cid='$cid'
		WHERE nickname='$nickname';
		");
		
		return;
	}
	
	public function getPlayerData(Player $player){
		(string) $nickname = strtolower($player->getName());
		
		$data = $this->database->query("SELECT * FROM `{$this->plugin->config->get('table_prefix')}pass` WHERE nickname='$nickname'");
		return $data;
	}
	
	public function setPassword(Player $player, $password){
		(string) $nickname = strtolower($player->getName());
		(string) $newpassword = password_hash($password, PASSWORD_DEFAULT);
		
		$this->database->query("UPDATE `{$this->plugin->config->get('table_prefix')}pass`
						SET password_hash='$newpassword' WHERE nickname='$nickname';
						");
		return;
	}
	
	public function deletePlayer(Player $player){
		(string) $nickname = strtolower($player->getName());
		$this->database->query("DELETE FROM `{$this->plugin->config->get('table_prefix')}pass` WHERE nickname='$nickname'");
		return;
	}
	
	public function registerPlayer(Player $player, $password){
		(string) $nickname = strtolower($player->getName());
		(int) $time = time();
		(string) $ip = $player->getAddress();
		(int) $cid = $player->getClientId();
		(string) $password = password_hash($password, PASSWORD_DEFAULT);
		
		$this->database->query(
				"INSERT INTO `{$this->plugin->config->get('table_prefix')}pass` 
				(nickname, firstlogin, lastlogin, password_hash, ip, cid) 
				VALUES 
				('$nickname', $time, $time, '$password', '$ip', '$cid');"
			);
		
		return;
	}

	public function close(){
		@$this->database->close();
		$this->plugin->getLogger()->info($this->lang->getMessage('db_disconnect'));
	}

}  