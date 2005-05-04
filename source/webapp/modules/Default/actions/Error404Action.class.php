<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003, 2004 Agavi Foundation.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org.                              |
// +---------------------------------------------------------------------------+

class Error404Action extends Action
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Execute any application/business logic for this action.
	 *
	 * In a typical database-driven application, execute() handles application
	 * logic itself and then proceeds to create a model instance. Once the model
	 * instance is initialized it handles all business logic for the action.
	 *
	 * A model should represent an entity in your application. This could be a
	 * user account, a shopping cart, or even a something as simple as a
	 * single product.
	 *
	 * @return mixed - A string containing the view name associated with this
	 *                 action, or...
	 *               - An array with three indices:
	 *                 0. The parent module of the view that will be executed.
	 *                 1. The parent action of the view that will be executed.
	 *                 2. The view that will be executed.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  1.0.0
	 */
	public function execute ()
	{

		// we don't need any data here because this action doesn't serve
		// any request methods, so the processing skips directly to the view

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the default view to be executed when a given request is not
	 * served by this action.
	 *
	 * @return mixed - A string containing the view name associated with this
	 *                 action, or...
	 *               - An array with three indices:
	 *                 0. The parent module of the view that will be executed.
	 *                 1. The parent action of the view that will be executed.
	 *                 2. The view that will be executed.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  1.0.0
	 */
	public function getDefaultView ()
	{

		return View::SUCCESS;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the request methods on which this action will process
	 * validation and execution.
	 *
	 * @return int - Request::GET - Indicates that this action serves only GET
	 *               requests, or...
	 *             - Request::POST - Indicates that this action serves only POST
	 *               requests, or...
	 *             - Request::NONE - Indicates that this action serves no
	 *               requests, or...
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  1.0.0
	 */
	public function getRequestMethods ()
	{

		return Request::NONE;

	}

}

?>
