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

namespace OCA\Registration\Controller;

use OCA\Registration\Api\Response as ApiResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use OCP\Util;
use OC_User;

class RegisterController extends Controller {

	private $mailer;
	private $l10n;
	private $urlgenerator;
	private $pendingreg;
	private $usermanager;
	private $config;
	private $groupmanager;
	/** @var \OCP\Defaults */
	private $defaults;
	private $random;
	private $usersession;
	protected $appName;

	public function __construct($appName, IRequest $request, IMailer $mailer, IL10N $l10n, $urlgenerator,
		$pendingreg, IUserManager $usermanager, IConfig $config, IGroupManager $groupmanager, Defaults $defaults,
		ISecureRandom $random, IUserSession $us) {
		$this->mailer = $mailer;
		$this->l10n = $l10n;
		$this->urlgenerator = $urlgenerator;
		$this->pendingreg = $pendingreg;
		$this->usermanager = $usermanager;
		$this->config = $config;
		$this->groupmanager = $groupmanager;
		$this->defaults = $defaults;
		$this->appName = $appName;
		$this->random = $random;
		$this->usersession = $us;
		parent::__construct($appName, $request);
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function indexPage() {
		$params = array();
		return new TemplateResponse('registration', 'index', $params, 'guest');
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function verifyPage($token) {
		$email = $this->pendingreg->findEmailByToken($token);
		if (!$email) {
			$url = $this->urlgenerator->linkToRoute('registration.register.indexPage');
			return new RedirectResponse($url);
		}
		$params = array('token' => $token, 'email' => $email);
		return new TemplateResponse('registration', 'verify', $params, 'guest');
	}

	/**
	 * @PublicPage
	 */
	public function registerHandler($email) {
		if (!$this->mailer->validateMailAddress($email)) {
			return (new ApiResponse([], Http::STATUS_UNPROCESSABLE_ENTITY))
				->setError($this->l10n->t('The email address you entered is not valid'));
		}

		if ($this->pendingreg->find($email)) {
			// Delete existing pending registration if we have one
			$this->pendingreg->delete($email);
			// .. and create a new one
			$token = $this->pendingreg->save($email);

			try {
				$this->sendValidationEmail($token, $email);
			} catch (\Exception $e) {
				\OCP\Util::logException($this->appName, $e, \OCP\Util::ERROR);
				return $this->getMailDeliveryFailedResponse();
			}

			return (new ApiResponse([], Http::STATUS_CONFLICT))
				->setError($this->l10n->t('There is already a pending registration with this email, a new verification email has been sent to the address.'));
		}

		if ($this->config->getUsersForUserValue('settings', 'email', $email)) {
			return (new ApiResponse([], Http::STATUS_CONFLICT))
				->setError($this->l10n->t('A user has already taken this email, maybe you already have an account?'));
		}

		// allow only from specific email domain
		$allowed_domains = $this->config->getAppValue($this->appName, 'allowed_domains', '');
		if ($allowed_domains !== '') {
			$allowed_domains = explode(';', $allowed_domains);
			$allowed = false;
			foreach ($allowed_domains as $domain) {
				$maildomain = explode("@", $email)[1];
				// valid domain, everythings fine
				if ($maildomain === $domain) {
					$allowed = true;
					break;
				}
			}
			if (!$allowed) {
				return (new ApiResponse([], Http::STATUS_IM_A_TEAPOT))
					->setError($this->l10n->t('Email domain is not allowed'));
			}
		}

		// TODO(leon): Data race here, we might already have pending requests in the DB
		// This is (currently) not a problem, but we should still ensure email addresses are unique
		$token = $this->pendingreg->save($email);
		try {
			$this->sendValidationEmail($token, $email);
		} catch (\Exception $e) {
			\OCP\Util::logException($this->appName, $e, \OCP\Util::ERROR);
			return $this->getMailDeliveryFailedResponse();
		}

		return (new ApiResponse([], Http::STATUS_OK))
			->setMessage($this->l10n->t('Verification email successfully sent.'));
	}

	private function getMailDeliveryFailedResponse() {
		return (new ApiResponse([], Http::STATUS_INTERNAL_SERVER_ERROR))
			->setError($this->l10n->t('A problem occurred sending email, please contact your administrator.'));
	}

	private function getInvalidTokenResponse() {
		return (new ApiResponse([], Http::STATUS_NOT_FOUND))
			->setError($this->l10n->t('Verification token not found.'));
	}

	/**
	 * @PublicPage
	 */
	public function verifyHandler($token) {
		$email = $this->pendingreg->findEmailByToken($token);
		if (!$email) {
			return $this->getInvalidTokenResponse();
		}

		return (new ApiResponse([], Http::STATUS_OK))
			->setAdditional('email', $email);
	}

	/**
	 * @PublicPage
	 * @UseSession
	 */
	public function createAccountHandler($token) {
		$email = $this->pendingreg->findEmailByToken($token);
		if (!$email) {
			return $this->getInvalidTokenResponse();
		}

		$fullname = $this->request->getParam('fullname');
		$username = $this->request->getParam('username');
		$password = $this->request->getParam('password');

		if (empty($fullname) || empty($username) || empty($password)) {
			return (new ApiResponse([], Http::STATUS_UNPROCESSABLE_ENTITY))
				->setError($this->l10n->t('Missing required parameters.'));
		}

		try {
			$user = $this->usermanager->createUser($username, $password);
		} catch (\Exception $e) {
			\OCP\Util::logException($this->appName, $e, \OCP\Util::ERROR);
			return (new ApiResponse([], Http::STATUS_INTERNAL_SERVER_ERROR))
				->setError($this->l10n->t('Failed to create user.'));
		}
		if (!$user) {
			return (new ApiResponse([], Http::STATUS_INTERNAL_SERVER_ERROR))
				->setError($this->l10n->t('Unable to create user, there are problems with the user backend.'));
		}

		$userId = $user->getUID();
		$user->setDisplayName($fullname);

		// Set user email
		$user->setEMailAddress($email);

		// Add user to group
		$registered_user_group = $this->config->getAppValue($this->appName, 'registered_user_group', 'none');
		if ($registered_user_group !== 'none') {
			try {
				$group = $this->groupmanager->get($registered_user_group);
				$group->addUser($user);
			} catch (\Exception $e) {
				\OCP\Util::logException($this->appName, $e, \OCP\Util::ERROR);
			}
		}

		// Delete pending reg request
		if (!$this->pendingreg->delete($email)) {
			\OCP\Util::writeLog($this->appName, 'Failed to delete pending registration request for ' . $email, \OCP\Util::ERROR);
		}

		// Notify admin
		$admin_users = $this->groupmanager->get('admin')->getUsers();
		$to_arr = array();
		foreach ($admin_users as $au) {
			$au_email = $this->config->getUserValue($au->getUID(), 'settings', 'email');
			if ($au_email !== '') {
				$to_arr[$au_email] = $au->getDisplayName();
			}
		}
		try {
			$this->sendNewUserNotifEmail($to_arr, $userId);
		} catch (\Exception $e) {
			\OCP\Util::writeLog($this->appName, 'Sending admin notification email failed: ' . $e->getMessage(), \OCP\Util::ERROR);
		}

		// Try to log user in
		if (method_exists($this->usersession, 'createSessionToken')) {
			$this->usersession->login($username, $password);
			$this->usersession->createSessionToken($this->request, $userId, $username, $password);
		} elseif (OC_User::login($username, $password)) {
			$this->cleanupLoginTokens($userId);
			// FIXME unsetMagicInCookie will fail from session already closed, so now we always remember
			$logintoken = $this->random->generate(32);
			$this->config->setUserValue($userId, 'login_token', $logintoken, time());
			OC_User::setMagicInCookie($userId, $logintoken);
		}

		return (new ApiResponse([], Http::STATUS_OK))
			->setMessage('Account created successfully.');
	}

	/**
	 * Sends validation email
	 * @param string $token
	 * @param string $to
	 * @return null
	 * @throws \Exception
	 */
	private function sendValidationEmail($token, $to) {
		$link = $this->urlgenerator->linkToRoute('registration.register.verifyPage', array('token' => $token));
		$link = $this->urlgenerator->getAbsoluteURL($link);
		$template_var = [
			'link' => $link,
			'sitename' => $this->defaults->getName(),
		];
		$html_template = new TemplateResponse('registration', 'email.validate_html', $template_var, 'blank');
		$html_part = $html_template->render();
		$plaintext_template = new TemplateResponse('registration', 'email.validate_plaintext', $template_var, 'blank');
		$plaintext_part = $plaintext_template->render();
		$subject = $this->l10n->t('Verify your %s registration request', [$this->defaults->getName()]);

		$from = Util::getDefaultEmailAddress('register');
		$message = $this->mailer->createMessage();
		$message->setFrom([$from => $this->defaults->getName()]);
		$message->setTo([$to]);
		$message->setSubject($subject);
		$message->setPlainBody($plaintext_part);
		$message->setHtmlBody($html_part);

		$failed_recipients = $this->mailer->send($message);
		if (!empty($failed_recipients)) {
			throw new \Exception('Failed recipients: ' . print_r($failed_recipients, true));
		}
	}

	/**
	 * Sends new user notification email to admin
	 * @param array $to
	 * @param string $username the new user
	 * @return null
	 * @throws \Exception
	 */
	private function sendNewUserNotifEmail(array $to, $username) {
		$template_var = [
			'user' => $username,
			'sitename' => $this->defaults->getName(),
		];
		$html_template = new TemplateResponse('registration', 'email.newuser_html', $template_var, 'blank');
		$html_part = $html_template->render();
		$plaintext_template = new TemplateResponse('registration', 'email.newuser_plaintext', $template_var, 'blank');
		$plaintext_part = $plaintext_template->render();
		$subject = $this->l10n->t('A new user "%s" has created an account on %s', [$username, $this->defaults->getName()]);

		$from = Util::getDefaultEmailAddress('register');
		$message = $this->mailer->createMessage();
		$message->setFrom([$from => $this->defaults->getName()]);
		$message->setTo($to);
		$message->setSubject($subject);
		$message->setPlainBody($plaintext_part);
		$message->setHtmlBody($html_part);

		$failed_recipients = $this->mailer->send($message);
		if (!empty($failed_recipients)) {
			throw new \Exception('Failed recipients: ' . print_r($failed_recipients, true));
		}
	}

	/**
	 * Replicates OC::cleanupLoginTokens() since it's protected
	 * @param string $userId
	 * @return null
	 */
	private function cleanupLoginTokens($userId) {
		$cutoff = time() - $this->config->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		$tokens = $this->config->getUserKeys($userId, 'login_token');
		foreach ($tokens as $token) {
			$time = $this->config->getUserValue($userId, 'login_token', $token);
			if ($time < $cutoff) {
				$this->config->deleteUserValue($userId, 'login_token', $token);
			}
		}
	}

}
