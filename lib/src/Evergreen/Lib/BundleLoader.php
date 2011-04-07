<?php
/**
 * Autoloader Class
 *
 * This is the class that automatically loads all classes in the framework. This class also parses the class name
 * and with that knows where to load the file from.
 *
 *
 * Copyright 2007-2011, NaturalCodeProject (http://www.naturalcodeproject.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright		Copyright 2007-2011, NaturalCodeProject (http://www.naturalcodeproject.com)
 * @package			evergreen
 * @subpackage		lib
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Evergreen\Lib;

/**
 * ClassLoader Class
 *
 * This is the class that automatically loads all classes in the framework. This class also parses the class name
 * and with that knows where to load the file from.
 *
 * @package       Evergreen
 * @subpackage    lib/Evergreen
 */
class BundleLoader {

  function __construct() {
    $bundles = func_get_args();

    var_dump($bundles);
  }

}
