<?php
/**
 * ownCloud - registration
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pellaeon Lin <pellaeon@hs.ntnu.edu.tw>
 * @author Julius Härtl <jus@bitgrid.net>
 * @copyright Pellaeon Lin 2014
 */

namespace OCA\Registration\Controller;

use OCA\Registration\Db\Registration;
use OCA\Registration\Service\MailService;
use OCA\Registration\Service\RegistrationException;
use OCA\Registration\Service\RegistrationService;
use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use \OCP\IL10N;
use \OCP\IConfig;

class RegisterController extends Controller {

	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlgenerator;
	/** @var RegistrationService */
	private $registrationService;
	/** @var MailService */
	private $mailService;


	public function __construct(
		$appName,
		IRequest $request,
		IL10N $l10n,
		IConfig $config,
		IURLGenerator $urlgenerator,
		RegistrationService $registrationService,
		MailService $mailService
	){
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->urlgenerator = $urlgenerator;
		$this->registrationService = $registrationService;
		$this->mailService = $mailService;
		$this->config = $config;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param $errormsg
	 * @param $entered
	 * @return TemplateResponse
	 */
	public function askEmail($errormsg, $entered) {
		$params = array(
			'errormsg' => $errormsg ? $errormsg : $this->request->getParam('errormsg'),
			'entered' => $entered ? $entered : $this->request->getParam('entered')
		);
		return new TemplateResponse('registration', 'register', $params, 'guest');
	}

	/**
	 * User POST email, if email is valid and not duplicate, we send token by mail
	 * @PublicPage
	 * @AnonRateThrottle(limit=5, period=1)
	 *
	 * @param string $email
	 * @param string $redirect_url
	 * @return TemplateResponse
	 */
	public function validateEmail($email, $redirect_url='') {//TODO rename to receiveUserEmail
		if (!$this->registrationService->checkAllowedDomains($email)) {//TODO Duplicate code with Service
			return new TemplateResponse('registration', 'domains', [
				'domains' => $this->registrationService->getAllowedDomains()
			], 'guest');
		}
		try {
			$reg = $this->registrationService->validateEmail($email);
			if ( $reg === true ) {
				$registration = $this->registrationService->createRegistration($email);
				if (!empty($redirect_url)) {
					$this->registrationService->updateRedirectUrl($registration, $redirect_url);
				}
				$this->mailService->sendTokenByMail($registration);
			} else {
				$this->registrationService->updateRedirectUrl($reg, $redirect_url);
				$this->registrationService->generateNewToken($reg);
				$this->mailService->sendTokenByMail($reg);
				return new TemplateResponse('registration', 'message', array('msg' =>
					$this->l10n->t('There is already a pending registration with this email, a new verification email has been sent to the address.')
				), 'guest');
			}
		} catch (RegistrationException $e) {
			return $this->renderError($e->getMessage(), $e->getHint());
		}


		return new TemplateResponse('registration', 'message', array('msg' =>
			$this->l10n->t('Verification email successfully sent.')
		), 'guest');
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param $token
	 * @return TemplateResponse
	 */
	public function verifyToken($token) {
		try {
			/** @var Registration $registration */
			$registration = $this->registrationService->verifyToken($token);
			$this->registrationService->confirmEmail($registration);

			// create account without form if username/password are already stored
			if ($registration->getUsername() !== "" && $registration->getPassword() !== "") {
				$this->registrationService->createAccount($registration);
				return new TemplateResponse('registration', 'message',
					['msg' => $this->l10n->t('Your account has been successfully created, you can <a href="%s">log in now</a>.', [$this->urlgenerator->getAbsoluteURL('/')])],
					'guest'
				);
			}

			// extra fields
			$showfullname = $this->config->getAppValue($this->appName, 'fullname', "no");
			$showcountry = $this->config->getAppValue($this->appName, 'country', "no");
			$showlanguage = $this->config->getAppValue($this->appName, 'language', "no");
			$showtimezone = $this->config->getAppValue($this->appName, 'timezone', "no");
			$showcompany = $this->config->getAppValue($this->appName, 'company', "no");
			$showphoneno = $this->config->getAppValue($this->appName, 'phoneno', "no");

			return new TemplateResponse('registration', 'form', ['email' => $registration->getEmail(), 'token' => $registration->getToken(),
					'showfullname' => $showfullname,
					'showcountry' => $showcountry,
					'showlanguage' => $showlanguage,
					'showtimezone' => $showtimezone,
					'showcompany' => $showcompany,
					'showphoneno' => $showphoneno], 'guest');
		} catch (RegistrationException $exception) {
			return $this->renderError($exception->getMessage(), $exception->getHint());
		}

	}

	/**
	 * @PublicPage
	 * @UseSession
	 *
	 * @param $token
	 * @return RedirectResponse|TemplateResponse
	 */
	public function createAccount($token) {
		$username = $this->request->getParam('username');
		$password = $this->request->getParam('password');
		$country = $this->request->getParam('country');
		$language = $this->request->getParam('language');
		$phoneno = $this->request->getParam('phoneno');
		$fullname = $this->request->getParam('fullname');
		$timezone = $this->request->getParam('timezone');
		$company = $this->request->getParam('company');
		$registration = $this->registrationService->getRegistrationForToken($token);

		// extra fields
		$showfullname = $this->config->getAppValue($this->appName, 'fullname', "no");
		$showcountry = $this->config->getAppValue($this->appName, 'country', "no");
		$showlanguage = $this->config->getAppValue($this->appName, 'language', "no");
		$showtimezone = $this->config->getAppValue($this->appName, 'timezone', "no");
		$showcompany = $this->config->getAppValue($this->appName, 'company', "no");
		$showphoneno = $this->config->getAppValue($this->appName, 'phoneno', "no");

		try {
			$user = $this->registrationService->createAccount($registration, $username, $password,
																$showfullname == "yes" ? $fullname : null,
																$showcountry == "yes" ? $country : null,
																$showlanguage == "yes" ? $language : null,
																$showphoneno == "yes" ? $phoneno : null,
																$showtimezone == "yes" ? $timezone : null,
																$showcompany == "yes" ? $company : null);
		} catch (\Exception $exception) {
			// Render form with previously sent values
			return new TemplateResponse('registration', 'form',
				[
					'email' => $registration->getEmail(),
					'entered_data' => array('user' => $username, 'fullname' => $fullname, 'country' => $country, 'language' => $language, 'phoneno' => $phoneno, 'timezone' => $timezone, 'company' => $company),
					'errormsgs' => array($exception->getMessage()),
					'token' => $token,
					'showfullname' => $showfullname,
					'showcountry' => $showcountry,
					'showlanguage' => $showlanguage,
					'showtimezone' => $showtimezone,
					'showcompany' => $showcompany,
					'showphoneno' => $showphoneno
				], 'guest');
		}

		if ($user->isEnabled()) {
			// log the user
			$result = $this->registrationService->loginUser($user->getUID(), $username, $password, false);
			$redirect_url = $registration->getRedirectUrl();
			if (!empty($redirect_url)) {
				$result = new RedirectResponse($redirect_url);
			}
			return $result;
		} else {
			// warn the user their account needs admin validation
			return new TemplateResponse(
				'registration',
				'message',
				array('msg' => $this->l10n->t("Your account has been successfully created, but it still needs approval from an administrator.")),
				'guest');
		}
	}

	private function renderError($error, $hint="") {
		return new TemplateResponse('registration', 'error', array(
			'errors' => array(array(
				'error' => $error,
				'hint' => $hint
			))
		), 'error');
	}

}
