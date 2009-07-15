

<h2>Welcome to BizMonster!</h2>

<p>You are about to experience an exciting new way build web sites. If you are seeing this page you have successfully installed the BizMonster Framework. For tutorials, code samples and troubleshooting ideas go to <a href="http://developer.bizmonster.com">developer.bizmonster.com</a>. There you can communicate with our dev monsters and other web developers about best practices on using this framework. </p>

<p>Congratulations!</p>
<br><br>
Sincerely,<br>	
<img src="[skin]/images/bizmonster.gif">

<br><br>
<?php
	var_dump($_POST);
	echo "<br />";
	var_dump($_GET);
?>
<form name="hello" update="$_POST" method="get" action="[site]/oranges">
	<input type="text" name="name" value="" /><br />
	<input type="submit" value="go" />
</form>

