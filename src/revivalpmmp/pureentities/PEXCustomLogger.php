<?php
/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities;

use pocketmine\Thread;
use pocketmine\ThreadManager;
use pocketmine\utils\TextFormat;
use pocketmine\Worker;

class PEXCustomLogger extends \AttachableThreadedLogger{
	/** @var \ClassLoader */
	protected $classLoader;
	protected $isKilled = false;
	/** @var string */
	protected $logFile;
	/** @var \Threaded */
	protected $logStream;
	/** @var  bool */
	protected $shutdown;
	/** @var bool */
	protected $logDebug;
	/** @var PEXCustomLogger */
	public static $logger = null;

	public $logDirectory = \pocketmine\DATA . "plugins" . DIRECTORY_SEPARATOR . "PureEntitiesX" . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR;

	/**
	 * CustomLogger constructor.
	 *
	 * @param bool $logDebug
	 *
	 * @throws \RuntimeException
	 */
	public function __construct(bool $logDebug = true){
		parent::__construct();
		if(static::$logger instanceof PEXCustomLogger){
			throw new \RuntimeException("PureEntitiesX Custom Logger has already been created.");
		}

		$logFile = $this->logDirectory . "PureEntitiesX_" . date("j.n.Y") . ".log";
		if(!file_exists($this->logDirectory)){
			mkdir($this->logDirectory);
		}
		touch($logFile);
		$this->logFile = $logFile;
		$this->logDebug = $logDebug;
		$this->logStream = new \Threaded;
		$this->start();
	}

	/**
	 * @return PEXCustomLogger
	 */
	public static function getLogger() : PEXCustomLogger{
		return static::$logger;
	}

	/**
	 * Assigns the CustomLogger instance to the {@link CustomLogger#logger} static property.
	 *
	 * WARNING: Because static properties are thread-local, this MUST be called from the body of every Thread if you
	 * want the logger to be accessible via {@link CustomLogger#getLogger}.
	 */
	public function registerStatic(){
		if(static::$logger === null or !static::$logger instanceof PEXCustomLogger){
			static::$logger = $this;
		}
	}

	public function emergency($message){
		$this->send($message, \LogLevel::EMERGENCY, "EMERGENCY", TextFormat::RED);
	}

	public function alert($message){
		$this->send($message, \LogLevel::ALERT, "ALERT", TextFormat::RED);
	}

	public function critical($message){
		$this->send($message, \LogLevel::CRITICAL, "CRITICAL", TextFormat::RED);
	}

	public function error($message){
		$this->send($message, \LogLevel::ERROR, "ERROR", TextFormat::DARK_RED);
	}

	public function warning($message){
		$this->send($message, \LogLevel::WARNING, "WARNING", TextFormat::YELLOW);
	}

	public function notice($message){
		$this->send($message, \LogLevel::NOTICE, "NOTICE", TextFormat::AQUA);
	}

	public function info($message){
		$this->send($message, \LogLevel::INFO, "INFO", TextFormat::WHITE);
	}

	public function debug($message, bool $force = false){
		if($this->logDebug === false and !$force){
			return;
		}
		$this->send($message, \LogLevel::DEBUG, "DEBUG", TextFormat::GRAY);
	}

	/**
	 * @param bool $logDebug
	 */
	public function setLogDebug(bool $logDebug){
		$this->logDebug = $logDebug;
	}

	public function logException(\Throwable $e, $trace = null){
		if($trace === null){
			$trace = $e->getTrace();
		}
		$errstr = $e->getMessage();
		$errfile = $e->getFile();
		$errno = $e->getCode();
		$errline = $e->getLine();

		$errorConversion = [
			0 => "EXCEPTION",
			E_ERROR => "E_ERROR",
			E_WARNING => "E_WARNING",
			E_PARSE => "E_PARSE",
			E_NOTICE => "E_NOTICE",
			E_CORE_ERROR => "E_CORE_ERROR",
			E_CORE_WARNING => "E_CORE_WARNING",
			E_COMPILE_ERROR => "E_COMPILE_ERROR",
			E_COMPILE_WARNING => "E_COMPILE_WARNING",
			E_USER_ERROR => "E_USER_ERROR",
			E_USER_WARNING => "E_USER_WARNING",
			E_USER_NOTICE => "E_USER_NOTICE",
			E_STRICT => "E_STRICT",
			E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
			E_DEPRECATED => "E_DEPRECATED",
			E_USER_DEPRECATED => "E_USER_DEPRECATED"
		];
		if($errno === 0){
			$type = \LogLevel::CRITICAL;
		}else{
			$type = ($errno === E_ERROR or $errno === E_USER_ERROR) ? \LogLevel::ERROR : (($errno === E_USER_WARNING or $errno === E_WARNING) ? \LogLevel::WARNING : \LogLevel::NOTICE);
		}
		$errno = $errorConversion[$errno] ?? $errno;
		$errstr = preg_replace('/\s+/', ' ', trim($errstr));
		$errfile = \pocketmine\cleanPath($errfile);
		$this->log($type, get_class($e) . ": \"$errstr\" ($errno) in \"$errfile\" at line $errline");
		foreach(\pocketmine\getTrace(0, $trace) as $i => $line){
			$this->debug($line, true);
		}
	}

	public function log($level, $message){
		switch($level){
			case \LogLevel::EMERGENCY:
				$this->emergency($message);
				break;
			case \LogLevel::ALERT:
				$this->alert($message);
				break;
			case \LogLevel::CRITICAL:
				$this->critical($message);
				break;
			case \LogLevel::ERROR:
				$this->error($message);
				break;
			case \LogLevel::WARNING:
				$this->warning($message);
				break;
			case \LogLevel::NOTICE:
				$this->notice($message);
				break;
			case \LogLevel::INFO:
				$this->info($message);
				break;
			case \LogLevel::DEBUG:
				$this->debug($message);
				break;
		}
	}

	public function shutdown(){
		$this->shutdown = true;
		$this->notify();
	}

	/**
	 * @param resource $logResource
	 */
	private function writeLogStream($logResource){
		while($this->logStream->count() > 0){
			$chunk = $this->logStream->shift();
			fwrite($logResource, $chunk);
		}
	}

	public function run(){
		$this->shutdown = false;
		$logResource = fopen($this->logFile, "ab");
		if(!is_resource($logResource)){
			throw new \RuntimeException("Couldn't open log file");
		}

		while($this->shutdown === false){
			$this->writeLogStream($logResource);
			$this->synchronized(function(){
				$this->wait(25000);
			});
		}

		$this->writeLogStream($logResource);

		fclose($logResource);
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
	protected function send($message, $level, $prefix, $color){
		$now = time();

		$thread = \Thread::getCurrentThread();
		if($thread === null){
			$threadName = "PEX";
		}elseif($thread instanceof Thread or $thread instanceof Worker){
			$threadName = $thread->getThreadName() . " thread";
		}else{
			$threadName = (new \ReflectionClass($thread))->getShortName() . " thread";
		}

		$message = TextFormat::toANSI(TextFormat::AQUA . "[" . date("H:i:s", $now) . "] " . TextFormat::RESET . $color . "[" . $threadName . "/" . $prefix . "]:" . " " . $message . TextFormat::RESET);
		$cleanMessage = TextFormat::clean($message);

		foreach($this->attachments as $attachment){
			if($attachment instanceof \ThreadedLoggerAttachment){
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