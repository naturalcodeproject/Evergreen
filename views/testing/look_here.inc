<?php if (!empty($this->flash['message'])): ?>
	<div style="font-size: 12px;color: 000000; border: 1px dashed #000000;background:#F7E8D9;padding:4px;margin: 7px 0px 7px 0px;"><?php echo $this->flash['message']; ?></div>
<?php endif; ?>
<form name="testform" update="$_POST" method="post" action="[controller]/validateform">
	First Name: <input type="text" name="first_name"><br/>
	Last Name: <input type="text" name="last_name"><br/>
	<input type="submit" value="GO">
</form>