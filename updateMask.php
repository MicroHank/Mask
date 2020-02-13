<?php
	/**
	 *  藥局口罩供應狀況：將即時公開資料寫入資料庫
	 *  資料來源：https://data.nhi.gov.tw/resource/mask/maskdata.csv
	 *  由地址處理 縣市區鄉鎮資料，儲存在 county 與 district 表單
	 *  將所有藥局供應現況，儲存在 pharmacy 表單
	 */
	include __DIR__."/vendor/autoload.php" ;

	use League\Csv\Reader ;
	use Henwen\Logger\Log ;

    //----------------------取得口罩公開資料--------------------------//
	try {
		$log = new Log() ;
		$log->info("取得口罩公開資料", __FILE__, array()) ;
		$source = file_get_contents("https://data.nhi.gov.tw/resource/mask/maskdata.csv") ;
		
		// 取得到檔案內容
		if (! empty($source)) {
			$log->info("處理資料", __FILE__, array()) ;
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
			
			$log->info("將資料寫入 maskdata_new.csv 檔案", __FILE__, array()) ;
			if (file_put_contents("csv/maskdata_new.csv", $source)) {
				$log->info("寫入 maskdata_new.csv 檔案成功", __FILE__, array()) ;
			}
		}
		else {
			$log->info("取得口罩公開資料：失敗", __FILE__, array()) ;
			exit ;
		}

		if (is_file("csv/maskdata_new.csv")) {
			$log->info("複製 maskdata_new.csv 至 maskdata.csv", __FILE__, array()) ;
			if (copy("csv/maskdata_new.csv", "csv/maskdata.csv")) {
				$log->info("複製 maskdata_new.csv 至 maskdata.csv 成功", __FILE__, array()) ;
			}
		}
	} catch (\Exception $e) {
		$log->info($e->getMessage(), __FILE__, array()) ;
		exit ;
	}
	//--------------------------------------------------------//

	try {
		$log->info("處理資料陣列：縣市區鄉鎮", __FILE__, array()) ;
		// Handle data to MySQL
		$reader = Reader::createFromPath('csv\maskdata.csv', 'r') ;
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
		$log->info($e->getMessage(), __FILE__, array()) ;
		exit ;
	}
	
	\DB::startTransaction() ;

	try {
		$log->info("寫入資料庫：縣市 與 區鄉鎮 資料", __FILE__, array()) ;
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
		$log->info($e->getMessage(), __FILE__, array()) ;
		exit ;
	}

	try {
		$log->info("寫入資料庫：新增或更新藥局口罩現況", __FILE__, array()) ;
		
		// 製作縣市區域對映鎮列
		$location = \DB::query("SELECT c.county_id, c.name AS county_name, d.district_id, d.name AS district_name FROM county AS c INNER JOIN district AS d ON c.county_id = d.county_id") ;
		
		// $location_map["縣市"]["鄉鎮"] = [縣市編號, 鄉鎮編號] ;
		$location_map = [] ; 
		foreach ($location as $obj) {
			$location_map[$obj["county_name"]][$obj["district_name"]] = [$obj["county_id"], $obj["district_id"]] ;
		}

		// 取出所有的藥局代碼
		$exists_pharmacy_code = \DB::queryFirstColumn("SELECT code FROM pharmacy") ;
		$add_pharmacy = 0 ;
		$update_pharmacy = 0 ;

		foreach ($data as $pharmacy) {
			$county = mb_substr($pharmacy[2], 0, 2) ; // 縣市
			$district = mb_substr($pharmacy[2], 3, 2) ; // 區鎮
			$location_id = $location_map[$county][$district] ; // [county_id, district_id]

			if (! in_array($pharmacy[0], $exists_pharmacy_code)) {
				\DB::insertIgnore("pharmacy", array(
					"code" 		  => $pharmacy[0],
					"county_id"   => (int) $location_id[0],
					"district_id" => (int) $location_id[1],
					"name" 		  => $pharmacy[1],
					"addr" 		  => $pharmacy[2],
					"phone" 	  => $pharmacy[3],
					"adult" 	  => $pharmacy[4],
					"kid"		  => $pharmacy[5],
					"updated_at"  => $pharmacy[6],
				)) ;
				$add_pharmacy++ ;
			}
			else {
				\DB::update("pharmacy", array(
					"adult" 	  => $pharmacy[4],
					"kid"		  => $pharmacy[5],
					"updated_at"  => $pharmacy[6],
				), "code=%s", $pharmacy[0]) ;
				$update_pharmacy++ ;
			}
		}

		\DB::commit() ;
		$log->info("新增藥局數：$add_pharmacy, 更新藥局數：$update_pharmacy", __FILE__, array()) ;

	} catch (\Exception $e) {
		$log->info($e->getMessage(), __FILE__, array()) ;
		exit ;
	}
	
?>