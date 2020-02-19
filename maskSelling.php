<?php
	include __DIR__."/vendor/autoload.php" ;
	
	use Henwen\Logger\Log ;

	$log = new Log() ;
	
	// 建立藥局代碼索引陣列
	// 例如 $data['2317060014'] = ["start" => "開始販賣時間", "end" => "口罩小於 10 的時間", "history" => [歷史紀錄]]
	$code = \DB::query("SELECT DISTINCT pd.code FROM pharmacy_day AS pd INNER JOIN pharmacy AS p ON pd.code = p.code ORDER BY pd.code, pd.updated_at") ;

	foreach ($code as $obj) {
		$data[$obj["code"]] = array("start" => "", "end" => "", "history" => array()) ;
	}
	
	// 取得本日藥局歷史紀錄
	$pharmacy = \DB::query("SELECT pd.code, pd.adult, pd.updated_at FROM pharmacy_day AS pd INNER JOIN pharmacy AS p ON pd.code = p.code ORDER BY pd.code, pd.updated_at") ;

	// 將每個藥局的歷史紀錄放到對應的代碼
	foreach ($pharmacy as $obj) {
		array_push($data[$obj["code"]]["history"], 
			array(
				"adult" => (int) $obj["adult"], 
				"updated_at" => $obj["updated_at"]
			)
		) ;
	}
	
	// 計算口罩販賣起始時間和小於 10 個的時間
	foreach ($data as $code => $array) {
		// $code = "2317060014"
		// $array = [
		//     "start" => "", "end" => "", 
		//     "history" => ["adult"=>127, "updated"=>"08:45"], ["adult"=>120, "updated"=>"08:50"], ...
		// ]
		$max_adult = 0 ;
		$has_saved_start = false ;
		
		foreach ($array["history"] as $value) {
			$this_adult = $value["adult"] ;
			$this_updated_at = $value["updated_at"] ;
			if ($max_adult <= $this_adult) {
				$max_adult = $this_adult ;
				continue ;
			}
			// 開始有販賣, 數值降低
			else if (! $has_saved_start && $max_adult > $this_adult){
				$data[$code]["start"] = $this_updated_at ;
				$has_saved_start = true ;
				continue ;
			}
			else if ($max_adult > $this_adult && $this_adult < 10) {
				$data[$code]["end"] = $this_updated_at ;
				break ;
			}
		}
	}

	// 更新販賣時間
	\DB::startTransaction() ;
	try {
		foreach ($data as $code => $array) {
			\DB::update("pharmacy", 
				array("start" => $data[$code]["start"], "end" => $data[$code]["end"]),
				"code = %s", $code
			) ;
		}
		\DB::commit() ;
		$log->info("更新藥局口罩販賣時間完成", __FILE__) ;
	} catch (\Exception $e) {
		\DB::rollback() ;
		$log->info($e->getMessage(), __FILE__) ;
		exit ;
	}
?>