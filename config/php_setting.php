<?php
	define("MASK_DIR", "E:/xampp/htdocs/mask") ;
	define("SOURCE_FILE", "https://data.nhi.gov.tw/resource/mask/maskdata.csv") ;

	ini_set("max_execution_time", 600) ;
	date_default_timezone_set("Asia/Taipei") ;

    // 註冊錯誤處理器
    set_error_handler(
	    function ($severity, $message, $file, $line) {
	        throw new \ErrorException($message, $severity, $severity, $file, $line) ;
	    }
	) ;

	function my_error_handler($params) {
		throw new \ErrorException($params["error"]." (".$params["query"].")", 1) ;
	}
?>