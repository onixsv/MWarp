<?php

namespace Mial7208\MWarp;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class MWarp extends PluginBase implements Listener{

	public array $mode = [];
	public array $warp = [];

	public Config $block;

	public array $bdata = [];

	protected function onEnable() : void{
		@mkdir($this->getDataFolder());
		$this->block = new Config($this->getDataFolder() . "warp.yml", Config::YAML);
		$this->bdata = $this->block->getAll();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	protected function onDisable() : void{
		$this->block->setAll($this->bdata);
		$this->block->save();
	}

	/**
	 * @param PlayerInteractEvent $ev
	 *
	 * @handleCancelled true
	 */
	public function onTouch(PlayerInteractEvent $ev){
		$player = $ev->getPlayer();
		$name = $player->getName();
		$block = $ev->getBlock();
		$x = $block->getPosition()->getX();
		$y = $block->getPosition()->getY();
		$z = $block->getPosition()->getZ();
		$world = $block->getPosition()->getWorld()->getFolderName();
		$pre = "§d<§f시스템§d>§f";

		if(!isset($this->mode[$name])){
			if(!isset($this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world])){
				return;
			}

			if(isset($this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world])){
				$xyz = explode(":", $this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world]);
				$move = new Position((float) $xyz[0], (float) ($xyz[1] + 0.1), (float) $xyz[2], $this->getServer()->getWorldManager()->getWorldByName($xyz[3]));
				$player->teleport($move, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
				$player->sendMessage("{$pre} 성공적으로 이동하였습니다.");
				return;
			}
		}

		if($this->mode[$name] == "도착"){
			if($ev->getBlock()->getId() == 0)
				return;
			$this->warp[$name] = "{$x}:{$y}:{$z}:{$world}";
			$this->mode[$name] = "생성";
			$player->sendMessage("{$pre} 성공적으로 목적지를 생성하였습니다.");
			$player->sendMessage("{$pre} 블럭을 터치하시면 출발지점이 설정됩니다.");
			return;
		}

		if($this->mode[$name] == "생성"){
			if($ev->getBlock()->getId() == 0)
				return;
			if(isset($this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world])){
				$player->sendMessage("{$pre} 이미 터치워프가 생성되어있습니다.");
				return;
			}
			if(!isset($this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world])){
				if($this->warp[$name] == "{$x}:{$y}:{$z}:{$world}"){
					$player->sendMessage("{$pre} 출발지점과 도착지점을 다르게 설정해주세요.");
					return;
				}
				$xyz = explode(":", $this->warp[$name]);
				$this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world] = $this->warp[$name];
				$player->sendMessage("{$pre} 성공적으로 터치워프를 생성하였습니다.");
				unset($this->mode[$name]);
				unset($this->warp[$name]);
				return;
			}
		}

		if($this->mode[$name] == "제거"){
			if($ev->getBlock()->getId() == 0)
				return;
			if(!isset($this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world])){
				return;
			}else{
				unset($this->bdata['Warp'][$x . ':' . $y . ':' . $z . ':' . $world]);
				unset($this->mode[$name]);
				$player->sendMessage("{$pre} 성공적으로 터치워프를 제거하였습니다.");
			}
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$pre = "§d<§f시스템§d>§f";
		if($command = "터치워프"){
			if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
				$sender->sendMessage("{$pre} 권한이 없습니다.");
				return true;
			}
			if(!isset($args[0])){
				$sender->sendMessage("--- 터치워프 도움말 1 / 1 ---");
				$sender->sendMessage("{$pre} /터치워프 생성 | 터치워프를 생성합니다.");
				$sender->sendMessage("{$pre} /터치워프 제거 | 터치워프를 제거합니다.");
				$sender->sendMessage("{$pre} /터치워프 취소 | 모든 작업을 취소합니다.");
				return true;
			}
			switch($args[0]){
				case '생성':
				case '설정':
				case '추가':
					$name = $sender->getName();
					$sender->sendMessage("{$pre} 터치워프를 생성합니다.");
					$sender->sendMessage("{$pre} 블럭을 터치하면 도착지점으로 설정됩니다.");
					$this->mode[$name] = "도착";
					return true;

				case '제거':
				case '삭제':
					$name = $sender->getName();
					$sender->sendMessage("{$pre} 터치워프를 제거합니다.");
					$sender->sendMessage("{$pre} 터치한 좌표의 워프표지판을 제거합니다.");
					$this->mode[$name] = "제거";
					return true;

				case '취소':
				case '중단':
					$name = $sender->getName();
					$sender->sendMessage("{$pre} 모든작업을 중단했습니다.");
					unset($this->mode[$name]);
					unset($this->warp[$name]);
					return true;

				default:
					$sender->sendMessage("--- 터치워프 도움말 1 / 1 ---");
					$sender->sendMessage("{$pre} /터치워프 생성 | 터치워프를 생성합니다.");
					$sender->sendMessage("{$pre} /터치워프 제거 | 터치워프를 제거합니다.");
					$sender->sendMessage("{$pre} /터치워프 취소 | 모든 작업을 취소합니다.");
					return true;
			}
		}
		return true;
	}

	public function onQuit(PlayerQuitEvent $ev){
		$name = strtolower($ev->getPlayer()->getName());
		unset($this->mode[$name]);
		unset($this->warp[$name]);
	}
}
