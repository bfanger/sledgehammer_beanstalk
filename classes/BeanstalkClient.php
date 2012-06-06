<?php
/**
 * BeanstalkClient
 */
namespace Sledgehammer;
/**
 * Client for the Beanstalk API
 * @link http://api.beanstalkapp.com/
 *
 * @package Beanstalk
 */
class BeanstalkClient extends Object {

	private $account;
	private $user;
	private $password;

	function __construct($account, $user = null, $password = null) {
		$this->account = $account;
		$this->user = $user;
		$this->password = $password;
	}

	function getRepositories() {
		return $this->multiple($this->buildUrl('repositories'), 'repository');
	}

	function getRepository($id) {
		return $this->single($this->buildUrl('repositories/'.$id), 'repository');
	}

	function getAccount($id) {
		return $this->single($this->buildUrl('accounts/'.$id), 'account');
	}

	function getUsers() {
		return $this->multiple($this->buildUrl('users'), 'user');
	}

	function getUser($id) {
		return $this->single($this->buildUrl('users/'.$id), 'user');
	}

	function getChangesets($options = array()) {
		return $this->multiple($this->buildUrl('changesets', $options), 'revision_cache');
	}

	function getChangesetsFor($repository, $options = array()) {
		$options['repository_id'] = $repository;
		return $this->multiple($this->buildUrl('changesets/repository', $options), 'revision_cache');
	}

	/**
	 * @param int|string $repository The id or name of the repository
	 * @return array
	 */
	function getReleasesFor($repository) {
		return $this->multiple($this->buildUrl($repository.'/releases'), 'release');
	}

	/**
	 * @param int|string $repository The id or name of the repository
	 * @return array
	 */
	function getTagsFor($repository) {
		return $this->multiple($this->buildUrl('repositories/'.$repository.'/tags'), 'tag');
	}

	/**
	 * @param int|string $repository The id or name of the repository
	 * @return array
	 */
	function getBranchesFor($repository) {
		return $this->multiple($this->buildUrl('repositories/'.$repository.'/branches'), 'branch');
	}

	private function single($url, $element) {
		$data = $this->fetchData($url);
		return $data[$element];
	}

	private function multiple($url, $element) {
		$data = $this->fetchData($url);
		$result = array();
		foreach ($data as $item) {
			$result[] = $item[$element];
		}
		return $result;
	}

	private function fetchData($url) {
		$responseText = file_get_contents($url);
		if ($responseText === false) {
			throw new InfoException('Beanstalk API Request failed', (string) $url);
		}
		if ($responseText === "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<releases type=\"array\"/>\n") {
//			notice('Incorrect response type (xml instead of json)', $url);
			return array();
		}
		return Json::decode($responseText, true);
	}

	/**
	 * Build a Beanstalk API url
	 *
	 * @param string $path
	 * @param array $params
	 * @return \SledgeHammer\URL
	 */
	private function buildUrl($path, $params = array()) {
		$url = new URL('https://'.$this->account.'.beanstalkapp.com/api/'.$path.'.json');
		$url->user = $this->user;
		$url->pass = $this->password;
		$url->query = $params;
		return $url;
	}

}

?>
