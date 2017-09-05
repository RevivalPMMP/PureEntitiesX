<?php

namespace revivalpmmp\pureentities;

use pocketmine\ThreadManager;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

class CustomLogger extends MainLogger {
	protected $logFile;

	/**
	 * CustomLogger constructor.
	 *
	 * @param bool $logDebug
	 *
	 * @throws \RuntimeException
	 */
	public function __construct(bool $logDebug = true) {
		\AttachableThreadedLogger::__construct();
		$logFile = \pocketmine\DATA."plugins".DIRECTORY_SEPARATOR."PureEntitiesX".DIRECTORY_SEPARATOR.date("j.n.Y").".log";
		touch($logFile);
		$this->logFile = $logFile;
		$this->logDebug = $logDebug;
		$this->logStream = new \Threaded;
		$this->start();
	}

	/**
	 * @param string $message
	 * @param string $level
	 * @param string $prefix
	 * @param string $color
	 */
	protected function send($message, $level, $prefix, $color) {
		$now = time();

		$threadName = (new \ReflectionClass(\Thread::getCurrentThread()))->getShortName() . " thread";

		$message = TextFormat::toANSI(TextFormat::AQUA . "[" . date("H:i:s", $now) . "] " . TextFormat::RESET . $color . "[" . $threadName . "/" . $prefix . "]:" . " " . $message . TextFormat::RESET);
		$cleanMessage = TextFormat::clean($message);

		foreach($this->attachments as $attachment) {
			if($attachment instanceof \ThreadedLoggerAttachment) {
				$attachment->call($level, $message);
			}
		}

		$this->logStream[] = date("Y-m-d", $now) . " " . $cleanMessage . PHP_EOL;
	}

	/**
	 * Stops the thread using the best way possible. Try to stop it yourself before calling this.
	 */
	public function quit(){
		$this->shutdown();
		$this->isKilled = true;

		if(!$this->isJoined()){
			if(!$this->isTerminated()){
				$this->join();
			}
		}
		unset(ThreadManager::getInstance()->{spl_object_hash($this)}); // TODO find a better method
	}
}