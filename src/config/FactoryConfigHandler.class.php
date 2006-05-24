<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2006 the Agavi Project.                                |
// | Based on the Mojavi3 MVC Framework, Copyright (c) 2003-2005 Sean Kerr.    |
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
 * AgaviFactoryConfigHandler allows you to specify which factory implementation 
 * the system will use.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @author     Mike Vincent <mike@agavi.org>
 * @copyright  (c) Authors
 * @since      0.9.0
 *
 * @version    $Id$
 */
class AgaviFactoryConfigHandler extends AgaviConfigHandler
{

	/**
	 * Execute this configuration handler.
	 *
	 * @param      string An absolute filesystem path to a configuration file.
	 *
	 * @return     string Data to be written to a cache file.
	 *
	 * @throws     <b>AgaviUnreadableException</b> If a requested configuration file
	 *                                             does not exist or is not readable.
	 * @throws     <b>AgaviParseException</b> If a requested configuration file is
	 *                                        improperly formatted.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function execute($config, $context = null)
	{
		if($context == null) {
			$context = '';
		}

		// parse the config file
		$configurations = $this->orderConfigurations(AgaviConfigCache::parseConfig($config)->configurations, AgaviConfig::get('core.environment'), $context);
		
		$code = array();
		foreach($configurations as $cfg) {

			$ctx = $context;
			if($cfg->hasAttribute('context'))
				$ctx = $cfg->getAttribute('context');

			$requiredItems = array('action_stack', 'controller', 'database_manager', 'dispatch_filter', 'execution_filter', 'filter_chain', 'logger_manager', 'request', 'storage', 'user', 'validator_manager');
			$definedItems = array_keys($cfg->getChildren());
			if(count($missingItems = array_diff($requiredItems, $definedItems)) > 0) {
					$error = 'Configuration file "%s" is missing key(s) %s';
					$error = sprintf($error, $config, implode(' ', $missingItems));
					throw new AgaviParseException($error);
			}
		

			// The order of this initialisiation code is fixed, to not change

			// Class names for ExecutionFilter, FilterChain and SecurityFilter
			$code[] = '$this->classNames["dispatch_filter"] = "' . $cfg->dispatch_filter->class->getValue() . '";';
			$code[] = '$this->classNames["execution_filter"] = "' . $cfg->execution_filter->class->getValue() . '";';
			$code[] = '$this->classNames["filter_chain"] = "' . $cfg->filter_chain->class->getValue() . '";';
			if(isset($cfg->security_filter)) {
				$code[] = '$this->classNames["security_filter"] = "' . $cfg->security_filter->class->getValue() . '";';
			}

			// Database
			if(AgaviConfig::get('core.use_database', false)) {
				$code[] = '$this->databaseManager = new ' . $cfg->database_manager->class->getValue() . '();';
				$code[] = '$this->databaseManager->initialize($this);';
			}

			// Actionstack
			$code[] = '$this->actionStack = new ' . $cfg->action_stack->class->getValue() . '();';

			// Request
			$code[] = '$this->request = AgaviRequest::newInstance("' . $cfg->request->class->getValue() . '");';

			// Storage
			$code[] = '$this->storage = AgaviStorage::newInstance("' . $cfg->storage->class->getValue() . '");';
			$code[] = '$this->storage->initialize($this, ' . $this->getSettings($cfg->storage) . ');';
			$code[] = '$this->storage->startup();';

			// ValidatorManager
			$code[] = '$this->validatorManager = new ' . $cfg->validator_manager->class->getValue() . '();';
			$code[] = '$this->validatorManager->initialize($this);';

			// User
			if(AgaviConfig::get('core.use_security', true)) {
				$code[] = '$this->user = AgaviUser::newInstance("' . $cfg->user->class->getValue() . '");';
				$code[] = '$this->user->initialize($this, ' . $this->getSettings($cfg->user) . ');';
			}

			// LoggerManager
			if(AgaviConfig::get('core.use_logging', false)) {
				$code[] = '$this->loggerManager = new ' . $cfg->logger_manager->class->getValue() . '();';
				$code[] = '$this->loggerManager->initialize($this);';

			}

			// Controller 
			$code[] = '$this->controller = AgaviController::newInstance("' . $cfg->controller->class->getValue() . '");';
			$code[] = '$this->controller->initialize($this, ' . $this->getSettings($cfg->controller) . ');';
	
			// Init Request
			$code[] = '$this->request->initialize($this, ' . $this->getSettings($cfg->request) . ');';
		
			if(isset($cfg->routing)) {
				// Routing
				$code[] = '$this->routing = new ' . $cfg->routing->class->getValue() . '();';
				$code[] = '$this->routing->initialize($this);';
				$code[] = 'include(AgaviConfigCache::checkConfig(AgaviConfig::get("core.config_dir") . "/routing.xml", $profile));';
			}
		}

		// compile data
		$retval = "<?php\n" .
		"// auto-generated by FactoryConfigHandler\n" .
		"// date: %s\n%s\n?>";
		$retval = sprintf($retval, date('m/d/Y H:i:s'), implode("\n", $code));

		return $retval;

	}

	protected function getSettings($itemNode)
	{
		$data = array();
		if($itemNode->hasChildren('parameters')) {
			foreach($itemNode->parameters as $node) {
				$data[$node->getAttribute('name')] = $node->getValue();
			}
		}
		return var_export($data, true);
	}

}

?>