<h3>:mask: 口罩地圖練習</h3>
<li>下載即時口罩資料: 來源 https://data.nhi.gov.tw/resource/mask/maskdata.csv</li>
<li>處理資料: 台/臺、全形/半形數字、擷取鄉鎮縣市</li>
<li>快速更新: 建立索引, 增加欄位更新效能</li>
<li>儲存藥局各時間點販售狀況: 了解開賣時間、販售接近完畢之時間</li>

[MySQL Schema]

Database: mask

Table: county, district, pharmacy,

CREATE DATABASE `mask` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `county` (

    `county_id` INT(2) NOT NULL auto_increment,

    `name` VARCHAR(10)  NOT NULL,

    PRIMARY KEY(`county_id`)

) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE `district` (

    `district_id` INT(2) NOT NULL auto_increment,

    `county_id` INT(2) NOT NULL,

    `name` VARCHAR(10)  NOT NULL,

    PRIMARY KEY(`district_id`),

    FOREIGN KEY (`county_id`) REFERENCES county(`county_id`)

) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE `pharmacy` (

    `code` char(10) NOT NULL,

    `county_id` INT(2) NOT NULL,

    `district_id` INT(2) NOT NULL,

    `name` VARCHAR(50) NOT NULL,

    `addr` VARCHAR(100) NOT NULL,

    `phone` VARCHAR(14) DEFAULT NULL,

    `adult` INT(2) DEFAULT 0,

    `kid` INT(2) DEFAULT 0,

    `updated_at` CHAR(20),

    `start` CHAR(5),

    `end` CHAR(5),

    PRIMARY KEY(`code`),

    KEY `county_id` (`county_id`),

    KEY `district_id` (`district_id`),

    KEY `start_end` (`start`,`end`),

    FOREIGN KEY (`county_id`) REFERENCES county(`county_id`),

    FOREIGN KEY (`district_id`) REFERENCES district(`district_id`)

) ENGINE=InnoDB CHARACTER SET=utf8;

CREATE TABLE `pharmacy_temp` (

    `code` char(10) NOT NULL,

    `county_id` INT(2) NOT NULL,

    `district_id` INT(2) NOT NULL,

    `name` VARCHAR(50) NOT NULL,

    `addr` VARCHAR(100) NOT NULL,

    `phone` VARCHAR(14) DEFAULT NULL,

    `adult` INT(2) DEFAULT 0,

    `kid` INT(2) DEFAULT 0,

    `updated_at`  CHAR(20),

    `start` CHAR(5),

    `end` CHAR(5),

) ENGINE=InnoDB CHARACTER SET=utf8;

// 每日口罩販賣各時間點數量

CREATE TABLE `pharmacy_day` (

    `code` char(10) NOT NULL,

    `adult` INT(2) DEFAULT 0,

    `kid` INT(2) DEFAULT 0,

    `updated_at` CHAR(5),

    `date` CHAR(10),

    KEY `code` (`code`),

    KEY `date` (`date`),

    KEY `updated_at` (`updated_at`)

) ENGINE=InnoDB CHARACTER SET=utf8;

// Event: 清除昨日的口罩販賣歷史紀錄

DELETE FROM pharmacy_day WHERE `date` = subdate(current_date, 1) ;

[Schema 說明]

1. TABLE `pharmacy` 索引 `start_end` (`start`,`end`)：快速更新所有藥局的販賣時間，針對所有藥局做 update，建立索引則可加速。
參考程式片段：

foreach ($data as $code => $array) {

    \DB::update("pharmacy", 

        array("start" => $data[$code]["start"], "end" => $data[$code]["end"]),

                   "code = %s", $code

        ) ;

}

2. 藥局代碼 code CHAR(10) 使用 10 個字元儲存

3. adult 與 kid 使用 INT(2) DEFAULT 0，使用兩個 byte 即能儲存口罩數量

[檔案與流程說明]

檔案「maskUpdateFromCSV.php」：取得公開資料 -> 將縣市區鄉鎮資料寫入 county, district -> 將藥局口罩現況寫入 pharmacy

1. 下載口罩販賣公開資料  https://data.nhi.gov.tw/resource/mask/maskdata.csv 檔案至 csv/maskdata_new.csv

2. 將 maskdata_new.csv 複製至 maskdata.csv，後續程式存取 maskdata.csv。

3. 讀取 maskdata.csv
    3-1 處理地址資料字串：例如臺->台、全形數字->半形數字...等。
    3-2 處理縣市區鄉鎮區資料，儲存至地區資料表 county、district。

4. 寫入資料庫；更新藥局口罩現況
    4-1 製作縣市鄉鎮區對應陣列
    4-2 將藥局資料包含縣市鄉鎮區編號，寫至 maskdata_new.csv 檔。
    4-3 從 maskdata_new.csv 寫入資料至暫存資料表 pharmacy_temp
    4-4 從 pharmacy_temp 更新(寫入)資料至 pharmacy，pharmacy 為讀取口罩現況使用之資料表。
    4-5 將當下抓到的口罩資料寫入 pharmacy_day，形成口罩販賣時間記錄。
    4-6 清除暫存資料表 pharmacy_temp

檔案「queryMask.php」：從 pharmacy 取得口罩販賣現況。

檔案「queryToday.php」：針對指定藥局，輸出販賣歷史記錄折線圖。

檔案「maskSelling.php」：計算藥局開始販賣時間與記錄剩餘小於 10 個口罩的時間點。
