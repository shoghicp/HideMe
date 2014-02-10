<?php

/*
__PocketMine Plugin__
name=HideMe
description=Hide yourself from other players, or disguise as other entities.
version=1.0.1
author=shoghicp
class=HideMe
apiversion=12
*/

class HideMe implements Plugin{
	private $api;
	private $config;
	private $hideEntities;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->hideEntities = array();
	}
	
	public function init(){
		DataPacketSendEvent::register(array($this, "dataPacketHandler"), EventPriority::HIGHEST);
		$this->api->console->register("hide", "[player]", array($this, "commandHandler"));
		//TODO: handle player leave
		console("[INFO] HideMe started");
	}
	
	public function dataPacketHandler(DataPacketSendEvent $event){
		$packet = $event->getPacket();
		
		if(($packet instanceof AddPlayerPacket) or ($packet instanceof MoveEntityPacket_PosRot) or ($packet instanceof RemovePlayerPacket)){
			if(isset($this->hideEntities[$packet->eid])){
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
					if(isset($this->hideEntities[$player->entity->eid])){
						$output = "You are now visible.\n";
						unset($this->hideEntities[$player->entity->eid]);						
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
						foreach($this->api->player->getAll() as $p){
							if($p->spawned === true and $p !== $player and $p->level !== $player->level){
								$p->dataPacket($pk);
							}
						}
						
						foreach($this->api->player->getAll($player->level) as $p){
							if($p->spawned === true and $p !== $player){
								$player->entity->spawn($p);
							}
						}
					}else{
						$output = "You are now hidden.\n";
						
						$pk = new RemovePlayerPacket;
						$pk->clientID = 0;
						$pk->eid = $player->entity->eid;
						foreach($this->api->player->getAll() as $p){
							if($p->spawned === true and $p !== $player){
								$p->dataPacket($pk);
							}
						}
						$this->hideEntities[$player->entity->eid] = true;
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