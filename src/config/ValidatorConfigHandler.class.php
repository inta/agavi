<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2005  Sean Kerr.                                       |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 * ValidatorConfigHandler allows you to register validators with the system.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @copyright (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since     0.9.0
 * @version   $Id$
 */
class ValidatorConfigHandler extends IniConfigHandler
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Execute this configuration handler.
	 *
	 * @param string An absolute filesystem path to a configuration file.
	 *
	 * @return string Data to be written to a cache file.
	 *
	 * @throws <b>UnreadableException</b> If a requested configuration file
	 *                                    does not exist or is not readable.
	 * @throws <b>ParseException</b> If a requested configuration file is
	 *                               improperly formatted.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function & execute ($config)
	{

		// set our required categories list and initialize our handler
		$categories = array('required_categories' => array('methods', 'names'));

		$this->initialize($categories);

		// parse the ini
		$ini = $this->parseIni($config);

		// init our data, includes, methods, names and validators arrays
		$data       = array();
		$includes   = array();
		$methods    = array();
		$names      = array();
		$validators = array();

		// get a list of methods and their registered files/parameters
		foreach ($ini['methods'] as $method => &$list) {

			$method = strtoupper($method);

			if (!isset($methods[$method])) {

				// make sure that this method is GET or POST
				if ($method != 'GET' && $method != 'POST') {

					// unsupported request method
					$error = 'Configuration file "%s" specifies unsupported ' .
							 'request method "%s"';
					$error = sprintf($error, $config, $method);

					throw new ParseException($method);

				}

				// create our method
				$methods[$method] = array();

			}

			if (trim($list) == '') {

				// we have an empty list of names
				continue;

			}

			// load name list
			$this->loadNames($config, $method, $methods, $names, $ini, $list);

		}

		// load attribute list
		$this->loadAttributes($config, $methods, $names, $validators, $ini, $list);

		// generate GET file/parameter data
		$data[] = "if (!isset(\$_SERVER['REQUEST_METHOD']) || \$_SERVER['REQUEST_METHOD'] == 'GET')";
		$data[] = "{";

		$this->generateRegistration('GET', $data, $methods, $names, $validators);

		// generate POST file/parameter data

		$data[] = "} else if (\$_SERVER['REQUEST_METHOD'] == 'POST')";
		$data[] = "{";

		$this->generateRegistration('POST', $data, $methods, $names, $validators);

		$data[] = "}";

		// compile data
		$retval = "<?php\n" .
				  "// auth-generated by ValidatorConfigHandler\n" .
				  "// date: %s\n%s\n%s\n?>";
		$retval = sprintf($retval, date('m/d/Y H:i:s'),
						  implode("\n", $includes), implode("\n", $data));

		return $retval;

	}

	// -------------------------------------------------------------------------

	/**
	 * Generate raw cache data.
	 *
	 * @param string A request method.
	 * @param array  The data array where our cache code will be appended.
	 * @param array  An associative array of request method data.
	 * @param array  An associative array of file/parameter data.
	 * @param array  A validators array.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function generateRegistration ($method, &$data, &$methods, &$names, &$validators)
	{

		// setup validator array
		$data[] = "\t\$validators = array();";

		// determine which validators we need to create for this request method
		if(isset($methods[$method])) {
	
			foreach ($methods[$method] as $name) {
	
				if (preg_match('/^([a-z0-9\-_]+)\{([a-z0-9\s\-_]+)\}$/i', $name, $match)) {
					// this file/parameter has a parent
					$subname = $match[2];
					$parent  = $match[1];
	
					$valList =& $names[$parent][$subname]['validators'];
				} else {
					// no parent
					$valList =& $names[$name]['validators'];
				}
	
				if ($valList == null) {
					// no validator list for this file/parameter
					continue;
				}
	
				foreach ($valList as &$valName) {
	
					if (isset($validators[$valName]) && !isset($validators[$valName][$method])) {
	
						// retrieve this validator's info
						$validator =& $validators[$valName];
	
						$tmp     = "\t\$validators['%s'] = new %s();\n";
						$tmp    .= "\t\$validators['%s']->initialize(%s, %s);";
						$data[]  = sprintf($tmp, $valName, $validator['class'],
						$valName, '$context',
						$validator['parameters']);
	
						// mark this validator as created for this request method
						$validators[$valName][$method] = true;
	
					}
	
				}
	
			}

			foreach ($methods[$method] as $name) {

				if (preg_match('/^([a-z0-9\-_]+)\{([a-z0-9\s\-_]+)\}$/i', $name, $match)) {
					// this file/parameter has a parent
					$subname = $match[2];
					$parent  = $match[1];
					$name    = $match[2];

					$attributes =& $names[$parent][$subname];
				} else {
					// no parent
					$attributes =& $names[$name];
				}

				// register file/parameter
				$tmp    = "\t\$validatorManager->registerName('%s', %s, %s, %s, " .
				      "%s, %s);";
				$data[] = sprintf($tmp, $name, $attributes['required'],
				              $attributes['required_msg'],
				              $attributes['parent'], $attributes['group'],
				              $attributes['file']);

				// register validators for this file/parameter
				foreach ($attributes['validators'] as &$validator) {
					$tmp    = "\t\$validatorManager->registerValidator('%s', %s, %s);";
					$data[] = sprintf($tmp, $name, "\$validators['$validator']", $attributes['parent']);
				}

			}

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Load the linear list of attributes from the [names] category.
	 *
	 * @param string The configuration file name (for exception usage).
	 * @param array  An associative array of request method data.
	 * @param array  An associative array of file/parameter names in which to
	 *               store loaded information.
	 * @param array  An associative array of validator data.
	 * @param array  The loaded ini configuration that we'll use for
	 *               verification purposes.
	 * @param string A comma delimited list of file/parameter names.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function loadAttributes (&$config, &$methods, &$names, &$validators, &$ini, &$list)
	{

		foreach ($ini['names'] as $key => &$value) {

			// get the file or parameter name and the associated info
			preg_match('/^(.*?)\.(.*?)$/', $key, $match);

			if (count($match) != 3) {

				// can't parse current key
				$error = 'Configuration file "%s" specifies invalid key "%s"';
				$error = sprintf($error, $config, $key);

				throw new ParseException($error);

			}

			$name      = $match[1];
			$attribute = $match[2];

			// get a reference to the name entry

			if (preg_match('/^([a-z0-9\-_]+)\{([a-z0-9\s\-_]+)\}$/i', $name, $match)) {

				// this name entry has a parent
				$subname = $match[2];
				$parent  = $match[1];

				if (!isset($names[$parent][$subname])) {

					// unknown parent or subname
					$error = 'Configuration file "%s" specifies unregistered ' .
							 'parent "%s" or subname "%s"';
					$error = sprintf($error, $config, $parent, $subname);

					throw new ParseException($error);

				}

				$entry =& $names[$parent][$subname];

			} else {

				// no parent
				if (!isset($names[$name])) {

					// unknown name
					$error = 'Configuration file "%s" specifies unregistered ' . 'name "%s"';
					$error = sprintf($error, $config, $name);

					throw new ParseException($error);

				}

				$entry =& $names[$name];

			}

			if ($attribute == 'validators') {

				// load validators for this file/parameter name
				$this->loadValidators($config, $validators, $ini, $value, $entry);

			} else if ($attribute == 'type') {

				// name type
				$lvalue = strtolower($value);

				if ($lvalue == 'file') {

					$entry['file'] = 'true';

				} else {

					$entry['file'] = 'false';

				}

			} else {

				// just a normal attribute
				$entry[$attribute] = $this->literalize($value);

			}

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Load all request methods and the file/parameter names that will be
	 * validated from the [methods] category.
	 *
	 * @param string The configuration file name (for exception usage).
	 * @param string A request method.
	 * @param array  An associative array of request method data.
	 * @param array  An associative array of file/parameter names in which to
	 *               store loaded information.
	 * @param array  The loaded ini configuration that we'll use for
	 *               verification purposes.
	 * @param string A comma delimited list of file/parameter names.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function loadNames (&$config, &$method, &$methods, &$names, &$ini, &$list)
	{

		// explode the list of names
		$array = explode(',', $list);

		// loop through the names
		foreach ($array as $name) {

			$name = trim($name);

			// make sure we have the required status of this file or parameter
			if (!isset($ini['names'][$name . '.' . 'required'])) {

				// missing 'required' attribute
				$error = 'Configuration file "%s" specifies file or ' .
						 'parameter "%s", but it is missing the "required" ' .
						 'attribute';
				$error = sprintf($error, $config, $name);

				throw new ParseException($error);

			}

			// determine parent status
			if (preg_match('/^([a-z0-9\-_]+)\{([a-z0-9\s\-_]+)\}$/i', $name, $match)) {

				// this name has a parent
				$subname = $match[2];
				$parent  = $match[1];

				if (!isset($names[$parent]) || !isset($names[$parent][$name])) {

					if (!isset($names[$parent])) {

						// create our parent
						$names[$parent] = array('_is_parent' => true);

					}

					// create our new name entry
					$entry                 = array();
					$entry['file']         = 'false';
					$entry['group']        = 'null';
					$entry['parent']       = "'$parent'";
					$entry['required']     = 'true';
					$entry['required_msg'] = "'Required'";
					$entry['validators']   = array();

					// add our name entry
					$names[$parent][$subname] = $entry;

				}

			} else if (strpos($name, '{') != false || strpos($name, '}') != false) {

				// name contains an invalid character
				// this is most likely a typo where the user forgot to add a
				// brace
				$error = 'Configuration file "%s" specifies method "%s" ' .
						 'with invalid file/parameter name "%s"';
				$error = sprintf($error, $config, $method, $name);

				throw new ParseException($error);

			} else {

				// no parent

				if (!isset($names[$name])) {

					// create our new name entry
					$entry                 = array();
					$entry['file']         = 'false';
					$entry['group']        = 'null';
					$entry['parent']       = 'null';
					$entry['required']     = 'true';
					$entry['required_msg'] = "'Required'";
					$entry['type']         = 'parameter';
					$entry['validators']   = array();

					// add our name entry
					$names[$name] = $entry;

				}

			}

			// add this name to the current request method
			$methods[$method][] = $name;

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Load a list of validators.
	 *
	 * @param string The configuration file name (for exception usage).
	 * @param array  An associative array of validator data.
	 * @param array  The loaded ini configuration that we'll use for
	 *               verification purposes.
	 * @param string A comma delimited list of validator names.
	 * @param array  A file/parameter name entry.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function loadValidators (&$config, &$validators, &$ini, &$list, &$entry)
	{

		// create our empty entry validator array
		$entry['validators'] = array();

		if (trim($list) == '') {

			// skip the empty list
			return;

		}

		// get our validator array
		$array = explode(',', $list);

		foreach ($array as &$validator) {

			$validator = trim($validator);

			// add this validator name to our entry
			$entry['validators'][] =& $validator;

			// make sure the specified validator exists
			if (!isset($ini[$validator])) {

				// validator hasn't been registered
				$error = 'Configuration file "%s" specifies unregistered ' .
						 'validator "%s"';
				$error = sprintf($error, $config, $validator);

				throw new ParseException($error);

			}

			// has it already been registered?
			if (isset($validators[$validator])) {

				continue;

			}

			if (!isset($ini[$validator]['class'])) {

				// missing class key
				$error = 'Configuration file "%s" specifies category ' .
						 '"%s" with missing class key';
				$error = sprintf($error, $config, $validator);

				throw new ParseException($error);

			}

			// create our validator
			$validators[$validator]               = array();
			$validators[$validator]['class']      = $ini[$validator]['class'];
			$validators[$validator]['file']       = null;
			$validators[$validator]['parameters'] = null;

			if (isset($ini[$validator]['file'])) {

				// we have a file for this validator
				$file = $ini[$validator]['file'];

				// keyword replacement
				$file = $this->replaceConstants($file);
				$file = $this->replacePath($file);

				if (!is_readable($file)) {

					// file doesn't exist
					$error = 'Configuration file "%s" specifies ' .
							 'category "%s" with nonexistent or unreadable ' .
							 'file "%s"';
					$error = sprintf($error, $config, $validator, $file);

					throw new ParseException($error);

				}

				$validators[$validator]['file'] = $file;

			}

			// parse parameters
			$parameters = ParameterParser::parse($ini[$validator]);

			$validators[$validator]['parameters'] = $parameters;

		}

	}

}

?>
