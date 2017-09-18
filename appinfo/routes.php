<?php
/**
 * ownCloud - registration
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pellaeon Lin <pellaeon@hs.ntnu.edu.tw>
 * @copyright Pellaeon Lin 2014
 */

return ['routes' => [
	array('name' => 'settings#admin', 'url' => '/settings', 'verb' => 'POST'),

	array('name' => 'register#indexPage', 'url' => '/', 'verb' => 'GET'),
	array('name' => 'register#verifyPage', 'url' => '/verify/{token}', 'verb' => 'GET'),

	array('name' => 'register#registerHandler', 'url' => '/api/v1/register', 'verb' => 'POST'),
	array('name' => 'register#verifyHandler', 'url' => '/api/v1/tokens/{token}', 'verb' => 'GET'),
	array('name' => 'register#createAccountHandler', 'url' => '/api/v1/register/{token}', 'verb' => 'POST'),
]];
