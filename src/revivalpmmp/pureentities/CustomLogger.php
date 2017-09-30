<?php

namespace revivalpmmp\pureentities;

use pocketmine\Thread;
use pocketmine\ThreadManager;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use pocketmine\Worker;

class CustomLogger extends MainLogger {
	/** @var \ClassLoader */
	protected $classLoader;
	protected $isKilled = false;
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
	 * Assigns the CustomLogger instance to the {@link CustomLogger#logger} static property.
	 *
	 * WARNING: Because static properties are thread-local, this MUST be called from the body of every Thread if you
	 * want the logger to be accessible via {@link CustomLogger#getLogger}.
	 */
	public function registerStatic(){
		if(static::$logger === null or !static::$logger instanceof CustomLogger){
			static::$logger = $this;
		}
	}

	/**
	 * Registers the class loader for this thread.
	 *
	 * WARNING: This method MUST be called from any descendant threads' run() method to make auto-loading usable.
	 * If you do not do this, you will not be able to use new classes that were not loaded when the thread was started
	 * (unless you are using a custom autoloader).
	 */
	public function registerClassLoader(){
		if(!interface_exists("ClassLoader", false)){
			require(\pocketmine\PATH . "src/spl/ClassLoader.php");
			require(\pocketmine\PATH . "src/spl/BaseClassLoader.php");
		}
		if($this->classLoader !== null){
			$this->classLoader->register(true);
		}
	}

	/**
	 * @param string $message
	 * @param string $level
	 * @param string $prefix
	 * @param string $color
	 */
	protected function send($message, $level, $prefix, $color) {
		$now = time();

		$thread = \Thread::getCurrentThread();
		if($thread === null){
			$threadName = "Server thread";
		}elseif($thread instanceof Thread or $thread instanceof Worker){
			$threadName = $thread->getThreadName() . " thread";
		}else{
			$threadName = (new \ReflectionClass($thread))->getShortName() . " thread";
		}

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