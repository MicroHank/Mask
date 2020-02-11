<?php
	\DB::$user = "root" ;
	\DB::$password = "" ;
	\DB::$dbName = "mask" ;
	\DB::$host = "127.0.0.1" ;
	\DB::$port = "3306" ; 
	\DB::$encoding = "utf8" ;

	// For MeekroDB Error Handler
	\DB::$throw_exception_on_error = true ; // throw exception on mysql query errors
	\DB::$throw_exception_on_nonsql_error = true ; // throw exception on library errors (bad syntax, etc)
	\DB::$error_handler = "my_error_handler" ; // runs on mysql query errors
	\DB::$nonsql_error_handler = "my_error_handler" ; // runs on library errors (bad syntax, etc)
?>