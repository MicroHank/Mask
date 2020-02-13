<?php
	include __DIR__."/vendor/autoload.php" ;

	use Henwen\Logger\Log ;

	\DB::startTransaction() ;
	$log = new Log() ;
	try {
		$log->info("清除口罩供應資料", __FILE__, array()) ;
		\DB::delete("pharmacy", 1) ;
		\DB::delete("district", 1) ;
		\DB::delete("county", 1) ;
		\DB::query("ALTER TABLE county AUTO_INCREMENT = 1") ;
		\DB::query("ALTER TABLE district AUTO_INCREMENT = 1") ;
	} catch (\Exception $e) {
		$log->info($e->getMessage(), __FILE__, array()) ;
	}
?>