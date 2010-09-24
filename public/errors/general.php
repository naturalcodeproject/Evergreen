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

	<title>404 Error</title>
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
			<h1>404 Error!</h1>
			<p>The page you were looking for does not exist.</p>
			<?php
				echo "<h3>Caught exception: ".Error::getMessage()."</h3>";
				echo "<p><PRE>";
					print_r(Error::getTrace());
				echo "</PRE></p>";
			?>
		</div>
	</div>

</body>
</html>
