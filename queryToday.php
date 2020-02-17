<html>
	<head>
		<title>查詢藥局本日口罩數量歷史紀錄</title>
		<meta charset="utf-8">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<style type="text/css">
			table{
				border-collapse:collapse ;
				collapse;border:1px solid black ;
			}
			td {
				collapse;border:1px solid black ;
				padding: 4px;
			}
			tr:hover {
				background-color: #ffff99;
			}
		</style>
	</head>
	<body>
		<?php
			include __DIR__."/vendor/autoload.php" ;

			$code = ! empty($_GET["code"]) ? filter_var($_GET["code"]) : "" ;
			
			$pharmacy = \DB::queryFirstRow("SELECT code, name, addr FROM pharmacy WHERE code = %s", $code) ;

			$data = \DB::query("SELECT adult, kid, updated_at FROM pharmacy_day WHERE code = %s", $code) ;
			
			echo "藥局代碼：".$pharmacy["code"]."<br />" ;
			echo "藥局名稱：".$pharmacy["name"]."<br />" ;
			echo "藥局地址：".$pharmacy["addr"]."<br />" ;

			echo "<table>" ;
			echo "<tr><td>更新時間</td><td>大人</td><td>小孩</td></tr>" ;
			foreach ($data as $obj) {
				echo "<tr>" ;
				echo "<td>".$obj["updated_at"]."</td>" ;
				echo "<td>".$obj["adult"]."</td>" ;
				echo "<td>".$obj["kid"]."</td>" ;
				echo "<tr />" ;
			}
			echo "</table>" ;
		?>	
	</body>
</html>