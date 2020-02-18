<html>
	<head>
		<title>查詢各區口罩現況數量排序</title>
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

			$district = ! empty($_GET["district"]) ? htmlentities($_GET["district"]) : "西屯" ;

			$sql = "SELECT p.code, p.name , p.addr, p.adult, p.kid, p.updated_at, p.start, p.end FROM pharmacy AS p 
					INNER JOIN district AS d ON p.district_id = d.district_id
					WHERE d.name = %s ORDER BY p.adult DESC, p.kid DESC" ;
			$pharmacy = \DB::query($sql, $district) ;
			
			echo "<table>" ;
			echo "<tr><td>藥局名稱</td><td>藥局地址</td><td>地圖</td><td>大人</td><td>小孩</td><td>開始販賣</td><td>數量小於10</td><td>更新時間</td><td>本日資料</td></tr>" ;
			foreach ($pharmacy as $obj) {
				echo "<tr>" ;
				echo "<td>".$obj["name"]."</td>" ;
				echo "<td>".$obj["addr"]."</td>" ;
				echo "<td>"."<a href='https://www.google.com/maps/place/".$obj["addr"]."' target='_blank'>地圖</a></td>" ;
				echo "<td>".$obj["adult"]."</td>" ;
				echo "<td>".$obj["kid"]."</td>" ;
				echo "<td>".$obj["start"]."</td>" ;
				echo "<td>".$obj["end"]."</td>" ;
				echo "<td>".$obj["updated_at"]."</td>" ;
				echo "<td>"."<a href='queryToday.php?code=".$obj["code"]."' target='_blank'>連結</a></td>" ;
				echo "<tr />" ;
			}
			echo "</table>" ;
		?>	
	</body>
</html>