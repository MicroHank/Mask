<?php
	/**
	 *  藥局口罩供應狀況：將即時公開資料寫入資料庫
	 *  資料來源：https://data.nhi.gov.tw/resource/mask/maskdata.csv
	 *  由地址處理 縣市區鄉鎮資料，儲存在 county 與 district 表單
	 *  將所有藥局供應現況，儲存在 pharmacy 表單
	 */
	include "E:/xampp/htdocs/mask/vendor/autoload.php" ;

	use League\Csv\Reader ;
	use Henwen\Logger\Log ;

    //----------------------取得口罩公開資料--------------------------//
	try {
		$log = new Log() ;
		$log->info("取得口罩公開資料", __FILE__) ;
		$source = file_get_contents(SOURCE_FILE) ;
		
		// 取得到檔案內容
		if (! empty($source)) {
			$log->info("處理來源資料字串, 讓資料較完整", __FILE__) ;
			$source = preg_replace("/臺北市/", "台北市", $source) ;
			$source = preg_replace("/臺中縣/", "台中市", $source) ;
			$source = preg_replace("/臺中市/", "台中市", $source) ;
			$source = preg_replace("/臺中巿北屯區/", "台中市北屯區", $source) ;
			$source = preg_replace("/臺南市/", "台南市", $source) ;
			$source = preg_replace("/臺東縣/", "台東縣", $source) ;
			$source = preg_replace("/９５０台東市/", "台東市", $source) ;
			$source = preg_replace("/為澎湖縣/", "澎湖縣", $source) ;
			$source = preg_replace("/淡水區新市一路３段１０３號/", "新北市淡水區新市一路３段１０３號", $source) ;
			// 全形數字
			$source = preg_replace("/１/", "1", $source) ;
			$source = preg_replace("/２/", "2", $source) ;
			$source = preg_replace("/３/", "3", $source) ;
			$source = preg_replace("/４/", "4", $source) ;
			$source = preg_replace("/５/", "5", $source) ;
			$source = preg_replace("/６/", "6", $source) ;
			$source = preg_replace("/７/", "7", $source) ;
			$source = preg_replace("/８/", "8", $source) ;
			$source = preg_replace("/９/", "9", $source) ;
			$source = preg_replace("/０/", "0", $source) ;
			
			$log->info("將資料寫入 maskdata_new.csv 檔案", __FILE__) ;
			if (! file_put_contents(MASK_DIR."/csv/maskdata_new.csv", $source)) {
				$log->info("寫入 maskdata_new.csv 檔案失敗", __FILE__) ;
			}
		}
		else {
			$log->info("取得口罩公開資料：失敗", __FILE__) ;
			exit ;
		}

		if (is_file(MASK_DIR."/csv/maskdata_new.csv")) {
			$log->info("複製 maskdata_new.csv 至 maskdata.csv", __FILE__) ;
			if (! copy(MASK_DIR."/csv/maskdata_new.csv", MASK_DIR."/csv/maskdata.csv")) {
				$log->info("複製 maskdata_new.csv 至 maskdata.csv 失敗", __FILE__) ;
			}
		}
	} catch (\Exception $e) {
		$log->info($e->getMessage(), __FILE__) ;
		exit ;
	}
	//--------------------------------------------------------//

	try {
		$log->info("處理資料陣列：縣市區鄉鎮", __FILE__) ;
		// Handle data to MySQL
		$reader = Reader::createFromPath(MASK_DIR."/csv/maskdata.csv", "r") ;
		$data = $reader->fetchAll() ;
		array_shift($data) ; // 移除標題列

		// 處理縣市鄉鎮至 MySQL Table county & district
		$location = [] ;
		foreach ($data as $pharmacy) {
			$county = mb_substr($pharmacy[2], 0, 2) ; // 縣市
			$district = mb_substr($pharmacy[2], 3, 2) ; // 區鎮
			
			// 儲存鄉鎮
			if (! isset($location[$county])){
				$location[$county] = [] ;
				array_push($location[$county], $district) ;
			}
			else {
				if(! in_array($district, $location[$county], true)) {
					array_push($location[$county], $district) ;
				}
			}
		}
	} catch (\Exception $e) {
		$log->info($e->getMessage(), __FILE__) ;
		exit ;
	}
	
	\DB::startTransaction() ;

	try {
		$log->info("寫入資料庫：縣市 與 區鄉鎮 資料", __FILE__) ;
		// Handle County
		foreach ($location as $county => $district_array) {
			$county_id = \DB::queryFirstField("SELECT county_id FROM county WHERE name = %s", $county) ;
			$county_id = (int) $county_id ;
			if (empty($county_id)) {
				\DB::insert("county", array("name" => $county)) ;
				$county_id = DB::insertId() ;
			}
		}

		foreach ($location as $county => $district_array) {
			$county_id = \DB::queryFirstField("SELECT county_id FROM county WHERE name = %s", $county) ;
			$county_id = (int) $county_id ;

			foreach ($district_array as $district) {
				$district_id = \DB::queryFirstField("SELECT district_id FROM district WHERE name = %s AND county_id = %d", $district, $county_id) ;
				if (empty($district_id)) {
					\DB::insert("district", array("county_id" => $county_id, "name" => $district)) ;
				}
			}
		}
		\DB::commit() ;

	} 
	catch (\Exception $e) {
		$log->info($e->getMessage(), __FILE__) ;
		exit ;
	}

	\DB::startTransaction() ;

	try {
		$log->info("寫入資料庫：更新藥局口罩現況", __FILE__) ;
		
		// 製作縣市區域對映鎮列
		$location = \DB::query("SELECT c.county_id, c.name AS county_name, d.district_id, d.name AS district_name FROM county AS c INNER JOIN district AS d ON c.county_id = d.county_id") ;
		
		// $location_map["縣市"]["鄉鎮"] = [縣市編號, 鄉鎮編號] ;
		$location_map = [] ; 
		foreach ($location as $obj) {
			$location_map[$obj["county_name"]][$obj["district_name"]] = [$obj["county_id"], $obj["district_id"]] ;
		}

		// 將藥局寫在 CSV 檔案
	    $target = MASK_DIR."/csv/maskdata_new.csv" ; 
	    $writer = \League\Csv\Writer::createFromPath($target) ;
	    $writer->setOutputBOM(\League\Csv\Reader::BOM_UTF8) ;
	    $writer->insertOne(["\xEF\xBB\xBF代碼", "縣市編號", "區鄉鎮編號", "名稱", "地址", "電話", "大人", "小孩", "更新"]) ;
		foreach ($data as $pharmacy) {
			$county = mb_substr($pharmacy[2], 0, 2) ; // 縣市
			$district = mb_substr($pharmacy[2], 3, 2) ; // 區鎮
			$location_id = $location_map[$county][$district] ; // [county_id, district_id]

	        $writer->insertOne([$pharmacy[0], (int) $location_id[0], (int) $location_id[1], $pharmacy[1], $pharmacy[2], $pharmacy[3], $pharmacy[4], $pharmacy[5], $pharmacy[6]]);
		}

		// 先將本次取得藥局資訊寫到暫存資料表 pharmacy_temp
		\DB::query("LOAD DATA LOCAL INFILE '$target' 
					INTO TABLE pharmacy_temp 
					FIELDS TERMINATED BY ',' 
					LINES TERMINATED BY '\n' 
					IGNORE 1 LINES
					(code, county_id, district_id, name, addr, phone, adult, kid, updated_at)") ;

		// 處理時間字串 "2020-02-15 20:10:30 移除第一個字元: League\CSV 寫入後的字元 ", 待處理
		\DB::query("UPDATE pharmacy_temp SET updated_at = substr(updated_at, 2)") ;

		// 計算處理藥局數量
		$total = \DB::queryFirstField("SELECT count(code) AS total FROM pharmacy_temp") ;
		$log->info("處理藥局數量：$total", __FILE__) ;

		// 從暫存資料表 pharmacy_temp 更新資料至正式資料表 pharmacy
		\DB::query("INSERT INTO pharmacy
					SELECT * FROM pharmacy_temp ON DUPLICATE KEY
					UPDATE adult = VALUES(adult), kid = VALUES(kid), updated_at = VALUES(updated_at)
			") ;

		// 從暫存資料表 pharmacy_temp 更新資料至正式資料表 pharmacy_day
		\DB::query("INSERT INTO pharmacy_day SELECT code, adult, kid,  TIME_FORMAT(`updated_at`, '%H:%i') AS updated_at, TIME_FORMAT(`updated_at`, '%Y-%m-%d') AS `date` FROM pharmacy_temp") ;

		// 移除暫存資料表的資料
		\DB::query("DELETE FROM pharmacy_temp") ;

		\DB::commit() ;
	} catch (\Exception $e) {
		$log->info($e->getMessage(), __FILE__) ;
		exit ;
	}
	
?>