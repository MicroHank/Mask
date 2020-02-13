<?php
	namespace Henwen\Logger ;

	use Monolog\Logger ;
	use Monolog\Handler\StreamHandler ;
	use Monolog\Handler\MongoDBHandler ;
	use Monolog\Formatter\LineFormatter ;

	class Log
	{
		// Class "LineFormatter" 實體與相關參數
		private $formatter ;
		private $log_date = "Y-m-d H:i:s" ;
		private $log_output = "[%datetime%] %channel%.%level_name%: %message% %context%\n" ;

		// MongoDB 連線資源、 連線資料庫名稱、連線集合名稱
		private $conn ;
		private $dbname ;
		private $collection ;

		public function __construct()
		{
			// Log 輸出格式
			$this->formatter = new LineFormatter($this->log_output, $this->log_date) ;
		}

		public function boot()
	    {
	        parent::boot() ;
	        Logger::setTimezone(new \DateTimeZone("Asia/Taipei")) ;
	    }

		public function info($message = "", $file = "", $context = [])
		{
			$logger = new Logger($file) ;
			$stream = new StreamHandler(__DIR__.'/../../log/info.log', Logger::INFO) ;
			$stream->setFormatter($this->formatter) ;
			$logger->pushHandler($stream) ;
			$logger->info($message, $context) ;
		}

		public function warning($message = "", $file = "", $context = [])
		{
			$logger = new Logger($file) ;
			$stream = new StreamHandler(__DIR__.'/../../log/info.log', Logger::WARNING) ;
			$stream->setFormatter($this->formatter) ;
			$logger->pushHandler($stream) ;
			$logger->info($message, $context) ;
		}

		public function error($message = "", $file = "", $context = [])
		{
			$logger = new Logger($file) ;
			$stream = new StreamHandler(__DIR__.'/../../log/info.log', Logger::ERROR) ;
			$stream->setFormatter($this->formatter) ;
			$logger->pushHandler($stream) ;
			$logger->info($message, $context) ;
		}


		public function setMongoDB($ip = "127.0.0.1", $dbname = "win_nexus", $collection = "monolog")
		{
			if (! filter_var($ip, FILTER_VALIDATE_IP)) {
				throw new \Exception("Invalid IP format") ;
			}

			$this->conn       = new \MongoClient("mongodb://". $ip) ;
			$this->dbname 	  = $dbname ;
			$this->collection = $collection ;
		}

		public function logMongoDB($message = "", $file = "", $context = [])
		{
			$logger = new Logger($file) ;
			$stream = new MongoDBHandler($this->conn, $this->dbname, $this->collection, Logger::DEBUG) ;
			$logger->pushHandler($stream) ;
			$logger->info("[IP=". IP::get() ."] ". $message, $context) ;
		}
	}
    
