<?php
	ini_set("max_execution_time", 300) ;

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