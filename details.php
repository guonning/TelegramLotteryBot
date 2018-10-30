<?php
$uri = explode('/',$_SERVER['REQUEST_URI']);

$number = $uri[3];
$hash = $uri[4];

if(!isset($uri[3]) || !isset($uri[4]) || !file_exists("./sessions/details/$hash.json"))
{
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if(file_exists("./sessions/details/$hash.json"))
{
	//$undefined = false;
	$json = file_get_contents("./sessions/details/$hash.json");
	$data = json_decode($json);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Azuki Lottery Bot</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" type="image/png" href="https://azuki.cloud/favicon.ico"/>
	<link rel="stylesheet" type="text/css" href="https://cdnjs.loli.net/ajax/libs/twitter-bootstrap/4.0.0-beta/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdnjs.loli.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdnjs.loli.net/ajax/libs/animate.css/3.5.2/animate.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdnjs.loli.net/ajax/libs/select2/4.0.3/css/select2.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.4.0/css/perfect-scrollbar.css">
	<link rel="stylesheet" type="text/css" href="/LotteryBot/assets/css/main.css">
</head>
<body>
	
	<div class="limiter">
		<div class="container-table100">
			<div class="wrap-table100">
				<div class="table100">
					<table>
						<thead>
							<tr class="table100-head">
								<th class="column1">编号</th>
								<th class="column2">Telegram ID</th>
								<th class="column3">用户名</th>
								<th class="column4">First Name</th>
								<th class="column5">参与时间</th>
								<th class="column6">Telegram</th>
							</tr>
						</thead>
						<tbody>
							<?php
							for($i = 0; $i < count($data); $i++)
							{
								$dt = $data[$i];
								if($dt->username == '') $dt->username = '<font style="color:#B0B0B0">(未设置用户名)</font>';
								if($dt->first_name == '') $dt->first_name = '<font style="color:#B0B0B0">(不支持的符号)</font>';
								echo "
									<tr>
										<td class=\"column1\">$dt->id</td>
										<td class=\"column2\">$dt->user_id</td>
										<td class=\"column3\">$dt->username</td>
										<td class=\"column4\">$dt->first_name</td>
										<td class=\"column5\">$dt->join_time</td>
										<td class=\"column6\"><button type=\"button\" class=\"btn btn-primary btn-sm\" onclick=\"window.location='tg://user?id=$dt->user_id'\">点我</button></td>
									</tr>
								";  // 还有 last name / language code 可自行魔改
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

<!--===============================================================================================-->	
	<script src="https://cdnjs.loli.net/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<!--===============================================================================================-->
	<script src="https://cdnjs.loli.net/ajax/libs/popper.js/1.12.5/umd/popper.min.js"></script>
	<script src="https://cdnjs.loli.net/ajax/libs/twitter-bootstrap/4.0.0-beta/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="https://cdnjs.loli.net/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="/LotteryBot/assets/js/main.js"></script>

</body>
</html>