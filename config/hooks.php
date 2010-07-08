<?php

Hook::add('Controller.designerFixCallback', 'test');

function test(&$var1 = '', &$var2 = '') {
	if ($var1 == '[custom]') {
		$var2 = '<p>This is my custom content</p>';
	}
}