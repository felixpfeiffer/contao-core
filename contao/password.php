<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Set the script name
 */
define('TL_SCRIPT', 'contao/password.php');


/**
 * Initialize the system
 */
define('TL_MODE', 'BE');
require dirname(__DIR__) . '/system/initialize.php';


/**
 * Class ChangePassword
 *
 * Handle back end password changes.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    Core
 */
class ChangePassword extends Backend
{

	/**
	 * Initialize the controller
	 *
	 * 1. Import the user
	 * 2. Call the parent constructor
	 * 3. Authenticate the user
	 * 4. Load the language files
	 * DO NOT CHANGE THIS ORDER!
	 */
	public function __construct()
	{
		$this->import('BackendUser', 'User');
		parent::__construct();

		$this->User->authenticate();

		System::loadLanguageFile('default');
		System::loadLanguageFile('modules');
	}


	/**
	 * Run the controller and parse the password template
	 */
	public function run()
	{
		$this->Template = new BackendTemplate('be_password');

		if (Input::post('FORM_SUBMIT') == 'tl_password')
		{
			$pw = Input::postRaw('password');
			$cnf = Input::postRaw('confirm');

			// The passwords do not match
			if ($pw != $cnf)
			{
				Message::addError($GLOBALS['TL_LANG']['ERR']['passwordMatch']);
			}
			// Password too short
			elseif (utf8_strlen($pw) < Config::get('minPasswordLength'))
			{
				Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['passwordLength'], Config::get('minPasswordLength')));
			}
			// Password and username are the same
			elseif ($pw == $this->User->username)
			{
				Message::addError($GLOBALS['TL_LANG']['ERR']['passwordName']);
			}
			// Save the data
			else
			{
				// Make sure the password has been changed
				if (crypt($pw, $this->User->password) === $this->User->password)
				{
					Message::addError($GLOBALS['TL_LANG']['MSC']['pw_change']);
				}
				else
				{
					$this->loadDataContainer('tl_user');

					// Trigger the save_callback
					if (is_array($GLOBALS['TL_DCA']['tl_user']['fields']['password']['save_callback']))
					{
						foreach ($GLOBALS['TL_DCA']['tl_user']['fields']['password']['save_callback'] as $callback)
						{
							if (is_array($callback))
							{
								$this->import($callback[0]);
								$pw = $this->$callback[0]->$callback[1]($pw);
							}
							elseif (is_callable($callback))
							{
								$pw = $callback($pw);
							}
						}
					}

					$objUser = UserModel::findByPk($this->User->id);
					$objUser->pwChange = '';
					$objUser->password = Encryption::hash($pw);
					$objUser->save();

					Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['pw_changed']);
					$this->redirect('contao/main.php');
				}
			}

			$this->reload();
		}

		$this->Template->theme = Backend::getTheme();
		$this->Template->messages = Message::generate();
		$this->Template->base = Environment::get('base');
		$this->Template->language = $GLOBALS['TL_LANGUAGE'];
		$this->Template->title = specialchars($GLOBALS['TL_LANG']['MSC']['pw_new']);
		$this->Template->charset = Config::get('characterSet');
		$this->Template->action = ampersand(Environment::get('request'));
		$this->Template->headline = $GLOBALS['TL_LANG']['MSC']['pw_change'];
		$this->Template->submitButton = specialchars($GLOBALS['TL_LANG']['MSC']['continue']);
		$this->Template->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$this->Template->confirm = $GLOBALS['TL_LANG']['MSC']['confirm'][0];

		$this->Template->output();
	}
}


/**
 * Instantiate the controller
 */
$objChangePassword = new ChangePassword();
$objChangePassword->run();
