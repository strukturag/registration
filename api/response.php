<?php

namespace OCA\Registration\Api;

use OCP\AppFramework\Http\DataResponse;

class Response extends DataResponse {
	private $response;

	public function __construct($params, $status) {
		parent::__construct($params, $status);
	}

	public function setAdditional($key, $value) {
		$this->data[$key] = $value;
		return $this;
	}

	public function setMessage($msg) {
		$this->setAdditional('message', $msg);
		return $this;
	}

	public function setError($error) {
		$this->setAdditional('error', $error);
		return $this;
	}
}
