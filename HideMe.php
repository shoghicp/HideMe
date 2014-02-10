<?php

/*
__PocketMine Plugin__
name=HideMe
description=Hide yourself from other players, or disguise as other entities.
version=1.0.0
author=shoghicp
class=HideMe
apiversion=12
*/

class HideMe implements Plugin{
	private $api;
	private $config;
	private $hideUsers;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->hideUsers = array();
	}
	
	public function init(){
		DataPacketSendEvent::register(array($this, "dataPacketHandler"), EventPriority::HIGHEST);
		$this->api->console->register("hide", "[player]", array($this, "commandHandler"));
		//TODO: handle player leave
		console("[INFO] HideMe started");
	}
	
	public function dataPacketHandler(DataPacketSendEvent $event){
		$packet = $event->getPacket();
		if($packet instanceof AddPlayerPacket or $packet instanceof MoveEntityPacket_PosRot){
			if(isset($this->hideUsers[$event->getPlayer()->username])){
				$event->setCancelled();
			}
		}
	}
	
	public function commandHandler($cmd, $params, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "hide":
				if($issuer instanceof Player and !isset($params[0])){
					$player = $issuer;
				}elseif(isset($params[0])){
					$player = $this->api->player->get($params[0]);
				}
				
				if($player instanceof Player){
					if(isset($this->hideUsers[$player->username])){
						$output = "You are now visible.\n";
						
						$pk = new AddPlayerPacket;
						$pk->clientID = 0;
						$pk->username = $player->username;
						$pk->eid = $player->entity->eid;
						$pk->x = -256;
						$pk->y = 128;
						$pk->z = -256;
						$pk->yaw = 0;
						$pk->pitch = 0;
						$pk->unknown1 = 0;
						$pk->unknown2 = 0;
						$pk->metadata = $player->entity->getMetadata();
						unset($this->hideUsers[$player->username]);
					}else{
						$output = "You are now hidden.\n";
						
						$pk = new RemovePlayerPacket;
						$pk->clientID = 0;
						$pk->eid = $player->entity->eid;
						$this->hideUsers[$player->username] = true;
					}
					
					foreach($this->api->player->getAll() as $p){
						if($p->spawned === true and $p !== $player){
							$p->dataPacket($pk);
						}
					}			
				}else{
					$output = "Usage: /$cmd [player]\n";
				}
				break;
		}
		return $output;
	}
	
	public function __destruct(){
	
	}
}