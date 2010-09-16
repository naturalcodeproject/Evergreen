<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	<title>evergreen</title>
	<link REL="stylesheet" type="text/css" href="[skin]/styles/style.css">
</head>
<body>
		<div align="center">	
			<div class="ncp">
	<a href="http://www.naturalcodeproject.com"><img src="[skin]/images/ncp.png" border="0"></a>

</div>

<div class="signin">
	<a href="http://www.getevergreen.com">evergreen documentation</a>
</div>



<div class="header">
	<div class="h3">
		<span class="header-title"><img src="[skin]/images/logo.png"></span>
	</div>

</div>

<div style="clear: both"></div>



			<div class="container">
			<div class="content-wrapper">
				
			<div id="container">
				<div id="content">
					<h1>High Five</h1>

					<?php
						echo "Welcome to Evergreen version ".Config::read("System.version");
					?>


					<p>
						<h2>Get Started</h2>
						For best results it is helpful to set the following permissions
					</p>

					<code>
					chmod -R 777 public/errors
					</code>

				</div>
				<div style="clear: both"></div>
			</div>
			
			
			</div>
			</div>
		
		<div class="footer">
			this site was built using evergreen
		</div>
	
</body>
</html>