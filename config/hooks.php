<?php

Hook::add('Reg.set.System.mode', array('Test_Helper', 'setSystemMode'));
Hook::add('Reg::set(System.mode)', array('Test_Helper', 'setSystemMode'));