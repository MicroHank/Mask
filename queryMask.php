<html>
	<head>
		<title>查詢各區口罩現況數量排序</title>
		<meta charset="utf-8">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	</head>
	<body>
		<?php
			include __DIR__."/vendor/autoload.php" ;

			$district = ! empty($_GET["district"]) ? htmlentities($_GET["district"]) : "西屯" ;

			$sql = "SELECT p.name , p.addr, p.adult, p.kid, p.updated_at FROM pharmacy AS p 
					INNER JOIN district AS d ON p.district_id = d.district_id
					WHERE d.name = %s ORDER BY p.adult DESC, p.kid DESC" ;
			$pharmacy = \DB::query($sql, $district) ;
			
			echo "<table>" ;
			echo "<tr><td>藥局名稱</td><td>藥局地址</td><td>大人</td><td>小孩</td><td>更新時間</td></tr>" ;
			foreach ($pharmacy as $obj) {
				echo "<tr>" ;
				echo "<td>".$obj["name"]."</td>" ;
				echo "<td><a href='https://www.google.com/maps/place/".$obj["addr"]."' target='_blank'>".$obj["addr"]."</a></td>" ;
				echo "<td>".$obj["adult"]."</td>" ;
				echo "<td>".$obj["kid"]."</td>" ;
				echo "<td>".$obj["updated_at"]."</td>" ;
				echo "<tr />" ;
			}
			echo "</table>" ;
		?>	
	</body>
</html>