<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
  <title>Evergreen Framework</title>
	<link REL="stylesheet" type="text/css" href="[skin]/styles/style.css">
	<script src="[skin]/javascript/projax/prototype.js" type="text/javascript"></script>
	<script src="[skin]/javascript/projax/scriptaculous.js" type="text/javascript"></script>
</head>
<body>
		<div align="center">	
			
		<div id="header">
		<table width="700" cellpadding="5" cellspacing="0" border="0">
			<tr>
				<td align="left">
					<div class="site_title">Your Logo</div>
				</td>
				<td align="left" valign="bottom">

				</td>
			</tr>
		</table>
		<table width="700" cellpadding="5" cellspacing="0" border="0">
			<tr>
				<td align="left">
					<div id="clientNavContainer">
					  <ul id="clientHeaderNavLinks">
						<li><a href="[site]" class="<?php echo $this->home_selected;?>">Home</a></li>
						<li><a href="[site]/developer" class="<?php echo $this->link1_selected;?>">Developer</a></li>
						<li><a href="[site]/developer/apples" class="<?php echo $this->link2_selected;?>">Apples</a></li>
						<li><a href="[site]/oranges" class="<?php echo $this->link3_selected;?>">Oranges</a></li>
						<li><a href="[site]/pickles" class="<?php echo $this->link3_selected;?>">Pickles</a></li>
						<!-- <li><a href="[site]/test" class="<?php echo $this->link3_selected;?>">Evergreen Route</a></li> -->
						<li><a href="[site]/developer/formtest" class="<?php echo $this->link3_selected;?>">Form Test</a></li>
						<li><a href="[site]/thisdoesntexist" class="<?php echo $this->link3_selected;?>">Error 404</a></li>
					  </ul>
					</div>
				</td>
			</tr>
		</table>
		</div>
		
		
		<table width="700" cellpadding="5" cellspacing="0" border="0">
			<tr>
				<td valign="top" align="left">
					<br>
					<?php echo $content_for_layout;?>
				</td>
			</tr>
		</table>
		
		</div>
		<?php
		//echo "<p />";
		
		//var_dump(Factory::get_config()->get_working_uri());
		//echo "<p />";
		//var_dump(Factory::get_config()->get_uri_map());
		?>
		<!--
		[site]="[site]"<br>
		[controller]="[controller]"<br>
		[view]="[view]"<br>
		[current]="[current]"<br>
		[skin]="[skin]"<br>
		<p>
		URI_ROOT=<?php //echo URI_ROOT;?><br>
		URI_SKIN=<?php //echo URI_SKIN;?><br>
		URI_CURRENT=<?php //echo URI_CURRENT;?><br>
		URI_VIEW=<?php //echo URI_VIEW;?><br>
		URI_CONTROLLER=<?php //echo URI_CONTROLLER;?><br>
	-->
</body>
</html>
