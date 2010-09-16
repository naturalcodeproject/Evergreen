<?php
if (Config::read("System.mode") != "development") {
	echo Config::read("Error.generalErrorMessage");
	exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>DB Error</title>
	<style type="text/css" media="screen">
		body {
		 background-color: #fff;
		 margin: 40px;
		 font-family: Lucida Grande, Verdana, Sans-serif;
		 font-size: 14px;
		 color: #4F5155;
		}
		
		a {
		 color: #003399;
		 background-color: transparent;
		 font-weight: normal;
		}
		
		h1 {
		 color: #444;
		 background-color: transparent;
		 font-size: 16px;
		 font-weight: bold;
		}
		
		.box{
			width: 360px;
			background-color: #E5ECF9;
			padding: 5px;
			border: 1px solid #C5D7EF;
			color: #3366FF;
		}
	</style>
</head>

<body>

	<div align="center">
		<div class="box">
			<h1>DB Error!</h1>
			<h3><?php echo Error::getMessage(); ?></h3>

			<?php 

			$params = Error::getParams();

			$dbMsg = $params['errorMessage'];
			$dbQuery = $params['query'];
			$dbTrace = $params['trace'];
			$dbValues = $params['queryValues'];

			?>
			<p><b>Failure:</b> <?php echo $dbMsg; ?></p>
			<p><b>Trace:</b><br/><PRE><?php print_r($dbTrace); ?></PRE></p>
			<p><b>Query:</b><br/><PRE><?php echo $dbQuery; ?></PRE></p>
			<p><b>Values:</b><br/><PRE><?php print_r( $dbValues ); ?></PRE></p>
		</div>
	</div>

</body>
</html>

