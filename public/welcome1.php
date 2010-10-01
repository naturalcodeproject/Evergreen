<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>
			evergreen
		</title>
		<link rel="stylesheet" type="text/css" href="[skin]/styles/style.css" />
		<style type="text/css" media="screen">
			body {
			 background-color: #fff;
			 margin: 0px;
			 font-family: "Lucida Grande", Verdana, sans-serif;
			 font-size: 13px;
			 color: #4F5155;
			 height: 100%;
			}
			.signin{
				height: auto;
				width: 95%;
				padding: 5px;
				text-align: right;
				font-size: 10px;
			}
			.signin a { color: #4F5155; text-decoration: none; font-family: "Lucida Grande", Verdana, sans-serif; }
			.signin a:visited { color: #4F5155; text-decoration: none; }
			.signin a:hover { color: #4F5155; text-decoration: none; }
			.header{
				height: 70px;
				width: 100%;
				background: #96D000;
				text-align: left;
			}
			.header-title{
				color: #fff;
				padding-left: 40px;
			}
			.footer{
				width: 95%;
				height: 50px;
				margin-top: 50px;
				padding: 10px;
				text-align: right;
				color: #D0D0D0;
				font-size: 9px;
			}
			.container{
				width: 90%;
				min-height: 300px;
				text-align: left;
			}
			h1{
				color: #4F5155;
				font-weight: normal;
				font-style: normal;
				font-size: 27px;
				margin-bottom: -5px;
			}
			h2{
				color: #A1C24F;
				font-weight: normal;
				font-style: normal;
			}
			.h3{
				color: #4F5155;
				padding-top: 20px;
				padding-bottom: 5px;
				font-weight: normal;
				font-style: normal;
				font-size: 32px;
			}
			.content{
				color: #969696;
				line-height: 21px;
			}
			.content-wrapper{
				padding: 20px;
			}
			.edit{
				text-align: right;
				width: 20px;
			}
			.delete{
				text-align: right;
				width: 20px;
			}
			pre {
			  background-color: #eee;
			  padding: 10px;
			  font-size: 11px;
			}
			a { padding-left: 5px; padding-right: 5px; color: #008000; text-decoration: none; font-family: "Lucida Grande", Verdana, sans-serif; }
			a:visited { color: #008000; text-decoration: none; }
			a:hover { color: #008000; text-decoration: none; }
			#list{
				color: #999;
			}
			#list td{
				border-bottom: 1px solid #D0D0D0;
			}
			#list th{
				border-bottom: 1px solid #D0D0D0;
				font-size: 15px;
			}
			/* START:notice */
			#notice {
			  border-top: 1px solid green;
			  border-bottom: 1px solid green;
			  padding-left: 25px;
			  padding-bottom: 5px;
			  padding-top: 5px;
			  margin-top: 2em;
			  margin-bottom: 2em;
			  background: url(../images/true.png) 2px no-repeat;
			  background-color: #d8f6d0;
			  font: bold smaller sans-serif;
			  color: green;
			}
			#error {
			  border-top: 1px solid red;
			  border-bottom: 1px solid red;
			  padding-left: 25px;
			  padding-bottom: 5px;
			  padding-top: 5px;
			  margin-top: 2em;
			  margin-bottom: 2em;
			  background: url(../images/false.png) 2px no-repeat;
			  background-color: #f6d0d0;
			  font: bold smaller sans-serif;
			  color: red;
			}
			/* END:notice */
			code {
			 font-family: Monaco, Verdana, Sans-serif;
			 font-size: 12px;
			 background-color: #f9f9f9;
			 border: 1px solid #D0D0D0;
			 color: #002166;
			 display: block;
			 margin: 14px 0 14px 0;
			 padding: 12px 10px 12px 10px;
			}
			.user-form{
				padding-top: 10px;
			}
			.user-form fieldset {
			  	background: #fff;
				border: 1px solid #ccc;
			}
			.user-form legend {
				color: #fff;
				background: #FF9900;
				font-family: sans-serif;
				padding: 0.2em 1em;
				font-size: 13px;
			}
			.user-form label {
			  width: 120px;
			  float: left;
			  text-align: right;
			  margin-right: 0.5em;
			  display: block;
			}
			.user-form .submit {
			  margin-left: 5.5em;
			}
			.bodyTitle{
				font-size: 21px;
				color: #52575d;
				padding-left: 11px;
			}
			.bodySubTitle{
				font-size: 14px;
				color: #feaf05;
				padding-left: 11px;
			}
			.bodyWrapper{
				line-height: 17px;
				padding-left: 11px;
			}
			#left{
				float: left;
				width: 45%;
			}
			#right{
				float: right;
				width: 45%;
			}
			
			/*------------------------------------------------------ 
			Main Navigation
			------------------------------------------------------*/
			#navContainer {
				width: auto;
			}
			ul#navLinks {
				list-style: none;
			}
			ul#navLinks li {
				float: left;
				font-family: "Lucida Grande", Verdana, sans-serif;
				font-size: 12px;
				padding-bottom: 4px;
			}
			#navLinks li a {
				padding: 5px;
				color: #ccc;
			}
			#navLinks li a:hover {
				color: #4F5155;
			}
			#navLinks li a.selected {
				color: #4F5155;
				background: #fff;
			}
			#list{padding: 0px; list-style-type: none; width: 100%;}
			#list li{margin: 0px; padding: 0px; border-bottom: 1px solid #ccc;}
			.list-item{
				width: 100%;
				padding:10px;
				position: relative;
				display: block;
			}
			.list-item-col1{
				padding:10px;
				text-align: left;
			}
			.list-item-col2{
				float: left;
				padding:10px;
			}
			.list-item-col3{
				float: right;
				padding:10px;
			}
			.list-item-col-last{
				float: right;
				padding:10px;
				text-align: right;
				clear: both;
			}
			.ncp{
				float: left;
				padding: 5px;
			}
			.ncp a { color: #fff; text-decoration: none; font-family: "Lucida Grande", Verdana, sans-serif; }
			.ncp a:visited { color: #fff; text-decoration: none; }
			.ncp a:hover { color: #fff; text-decoration: none; }
		</style>
	</head>
	<body>
		<div align="center">
			<div class="ncp">
				<a href="http://www.naturalcodeproject.com"><img src="data:img/png;base64,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" border="0" /></a>
			</div>
			<div class="signin">
				<a href="http://github.com/naturalcodeproject/Evergreen/wiki">evergreen documentation</a>
			</div>
			<div class="header">
				<div class="h3">
					<span class="header-title"><img src="data:image/png;base64,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
					LrUAAAAASUVORK5CYII=" /></span>
				</div>
			</div>
			<div style="clear: both"></div>
			<div class="container">
				<div class="content-wrapper">
					<div id="container">
						<div id="content">
							<h1>High Five</h1>
							
							<?php echo "Welcome to Evergreen version ".Config::read("System.version"); ?>
							
							<h2>Get Started</h2>
							
							<p>You will be routed to this page until it is removed. It may be found at <em>/public/welcome.php</em></p>
							
							<p>For more information please visit the <a href="http://github.com/naturalcodeproject/Evergreen/wiki" target="_blank">documentation</a>.</p>
						</div>
						<div style="clear: both"></div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
