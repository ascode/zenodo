<?php
/**
 * Zenodo - Publish your work to Zenodo.org
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Zenodo\AppInfo;

use \OCA\Zenodo\Controller\SettingsController;
use \OCA\Zenodo\Controller\ZenodoController;
use \OCA\Zenodo\Service\ConfigService;
use \OCA\Zenodo\Service\ApiService;
use \OCA\Zenodo\Service\FileService;
use \OCA\Zenodo\Service\MiscService;
use \OCA\Zenodo\Db\DepositionFilesMapper;
use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

	/**
	 * @param array $params
	 */
	public function __construct(array $params = array()) {
		parent::__construct('zenodo', $params);
		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService(
			'MiscService', function ($c) {
			return new MiscService($c->query('Logger'), $c->query('AppName'));
		}
		);


		$container->registerService(
			'ConfigService', function ($c) {
			return new ConfigService(
				$c->query('AppName'), $c->query('CoreConfig'), $c->query('UserId'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'ApiService', function ($c) {
			return new ApiService(
				$c->query('ConfigService'), $c->query('FileService'), $c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'FileService', function ($c) {
			return new FileService(
				$c->query('UserId'), $c->query('ConfigService'), $c->query('MiscService')
			);
		}
		);

		/**
		 * Controllers
		 */
		$container->registerService(
			'SettingsController', function ($c) {
			return new SettingsController(
				$c->query('AppName'), $c->query('Request'), $c->query('ConfigService'),
				$c->query('MiscService')
			);
		}
		);

		$container->registerService(
			'ZenodoController', function ($c) {
			return new ZenodoController(
				$c->query('AppName'), $c->query('Request'), $c->query('UserId'),
				$c->query('UserManager'),
				$c->query('ConfigService'),
				$c->query('ApiService'),
				$c->query('DepositionFilesMapper'),
				$c->query('MiscService')
			);
		}
		);


		/**
		 * Mapper
		 */
		$container->registerService(
			'DepositionFilesMapper', function ($c) {
			return new DepositionFilesMapper(
				$c->query('ServerContainer')
				  ->getDatabaseConnection()
			);
		}
		);

		/**
		 * Core
		 */
		$container->registerService(
			'Logger', function ($c) {
			return $c->query('ServerContainer')
					 ->getLogger();
		}
		);
		$container->registerService(
			'CoreConfig', function ($c) {
			return $c->query('ServerContainer')
					 ->getConfig();
		}
		);

		$container->registerService(
			'UserId', function ($c) {
			$user = $c->query('ServerContainer')
					  ->getUserSession()
					  ->getUser();

			return is_null($user) ? '' : $user->getUID();
		}
		);

		$container->registerService(
			'UserManager', function ($c) {
			return $c->query('ServerContainer')
					 ->getUserManager();
		}
		);
	}


	public function registerInFiles() {
		\OC::$server->getEventDispatcher()
					->addListener(
						'OCA\Files::loadAdditionalScripts', function () {
						// add some animation
						\OCP\Util::addScript('zenodo', 'jquery.animate-shadow-min');
						\OCP\Util::addScript('zenodo', 'navigate');
						\OCP\Util::addScript('zenodo', 'dialog');
						\OCP\Util::addStyle('zenodo', 'navigate');
					}
					);
	}


	public function registerSettingsAdmin() {
		\OCP\App::registerAdmin(
			$this->getContainer()
				 ->query('AppName'), 'lib/admin'
		);
	}
}

