<html>
	<head>
		<title>查詢藥局本日口罩數量歷史紀錄</title>
		<meta charset="utf-8">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script src="Highcharts/code/highcharts.js"></script>
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

			echo "藥局代碼：".$pharmacy["code"]."<br />" ;
			echo "藥局名稱：".$pharmacy["name"]."<br />" ;
			echo "藥局地址：".$pharmacy["addr"]."<br />" ;
		?>

		<div id="container"></div>
		
		<?php
			$data = \DB::query("SELECT adult, kid, updated_at FROM pharmacy_day WHERE code = %s", $code) ;
			// 繪製表單
			echo "<div><table>" ;
			echo "<tr><td>更新時間</td><td>大人</td><td>小孩</td></tr>" ;
			$x_time = array() ;
			$y_adult = array() ;
			$y_kid = array() ;
			foreach ($data as $obj) {
				echo "<tr>" ;
				echo "<td>".$obj["updated_at"]."</td>" ;
				echo "<td>".$obj["adult"]."</td>" ;
				echo "<td>".$obj["kid"]."</td>" ;
				echo "<tr />" ;
				array_push($x_time, "'".$obj["updated_at"]."'") ;
				array_push($y_adult, $obj["adult"]) ;
				array_push($y_kid, $obj["kid"]) ;
			}
			echo "</table></div>" ;
		?>
		<script type="text/javascript">
			var x_time = <?php echo "[".join(",", $x_time)."]" ?> ;
			var y_adult = <?php echo "[".join(",", $y_adult)."]" ?> ;
			var y_kid = <?php echo "[".join(",", $y_kid)."]" ?> ;

			Highcharts.chart('container', {
				chart: {
			        type: 'line'
			    },
			    title: {
			        text: ' 本日口罩現況'
			    },
			    subtitle: {
			        text: '資料來源：https://data.nhi.gov.tw/resource/mask/maskdata.csv'
			    },
			 	xAxis: {
			        categories: x_time
			    },
			    yAxis: {
			    	min: 0,
			    	max: 150,
			        title: {
			            text: '口罩數量'
			        }
			    },
			    legend: {
			        layout: 'vertical',
			        align: 'right',
			        verticalAlign: 'middle'
			    },
			    plotOptions: {
			         line: {
			            dataLabels: {
			                enabled: true
			            },
			            enableMouseTracking: false
			        }
			    },

			    series: [{
			        name: '大人',
			        data: y_adult
			    },{
			        name: '小孩',
			        data: y_kid
			    }],
			    responsive: {
			        rules: [{
			            condition: {
			                maxWidth: 500
			            },
			            chartOptions: {
			                legend: {
			                    layout: 'horizontal',
			                    align: 'center',
			                    verticalAlign: 'bottom'
			                }
			            }
			        }]
			    }

			});
		</script>
	</body>
</html>