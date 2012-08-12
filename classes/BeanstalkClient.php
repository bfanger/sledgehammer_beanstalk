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

	/**
	 * Account name: $account.beanstalkapp.com
	 * @var string
	 */
	private $account;

	/**
	 * Username
	 * @var string
	 */
	private $username;

	/**
	 * Password
	 * @var string
	 */
	private $password;

	/**
	 * Log the api requests.
	 * @var Logger
	 */
	private $logger;

	function __construct($account, $username = null, $password = null) {
		$this->account = $account;
		$this->username = $username;
		$this->password = $password;
		$this->logger = new Logger(array(
				'identifier' => 'Beanstalk',
				'columns' => array('Request', 'Duration'),
				'plural' => 'requests',
				'renderer' => array($this, 'renderLog')
			));
	}

	/**
	 * Fetch all repositories. (in the account the user has access to)
	 * @return array
	 */
	function getRepositories() {
		return $this->multiple($this->buildUrl('repositories'), 'repository');
	}

	/**
	 * Fetch info about a repository.
	 * @param string|int $id  ID or Repository URL ($account.beanstalkapp.com/$id)
	 * @return array
	 */
	function getRepository($id) {
		return $this->single($this->buildUrl('repositories/'.$id), 'repository');
	}

	/**
	 * Fetch info about the account
	 * @param int $id
	 * @return array
	 */
	function getAccount($id) {
		return $this->single($this->buildUrl('accounts/'.$id), 'account');
	}

	/**
	 * Fetch all users in the account.
	 * @return array
	 */
	function getUsers() {
		return $this->multiple($this->buildUrl('users'), 'user');
	}

	/**
	 * Fetch info about a user.
	 * @param int $id
	 * @return array
	 */
	function getUser($id) {
		return $this->single($this->buildUrl('users/'.$id), 'user');
	}

	/**
	 * Retrieve changesets from all repositories available to the logged in user.
	 * @link http://api.beanstalkapp.com/changeset.html
	 *
	 * @param array $options  Optional options:
	 *   page (integer) — page number for pagination.
	 *   per_page (integer) — number of elements per page (default 15, maximum 30);
	 *   order_field (string) — what column to use for ordering (default is time);
	 *   order (string) — order direction. Should be either ASC or DESC (default is DESC).
	 *   all - (bool) return an Iterator that will automaticly retrieve the next page.
	 *
	 * @return \Traversable
	 */
	function getChangesets($options = array()) {
		if (array_value($options, 'all')) {
			unset($options['all']);
			return new BeanstalkPagedResult($this->buildUrl('changesets', $options), 'revision_cache');
		}
		return $this->multiple($this->buildUrl('changesets', $options), 'revision_cache');
	}

	/**
	 * Retrieve changesets from a repository.
	 * @link http://api.beanstalkapp.com/changeset.html
	 *
	 * @param array $options  Optional options:
	 *   page (integer) — page number for pagination.
	 *   per_page (integer) — number of elements per page (default 15, maximum 30);
	 *   order_field (string) — what column to use for ordering (default is time);
	 *   order (string) — order direction. Should be either ASC or DESC (default is DESC).
	 *   all - (bool) return an Iterator that will automaticly retrieve the next page.
	 *
	 * @return \Traversable
	 */
	function getChangesetsFor($repository, $options = array()) {
		$options['repository_id'] = $repository;
		if (array_value($options, 'all')) {
			unset($options['all']);
			return new BeanstalkPagedResult($this->buildUrl('changesets/repository', $options), 'revision_cache');
		}
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

	/**
	 * Render a log entry.
	 *
	 * @param string $url
	 * @param array $params
	 */
	function renderLog($url, $params) {
		echo '<td><a href="https://', $this->account, '.beanstalkapp.com', $url, '" target="_blank">', HTML::escape($url), '</a></td>';
		echo '<td>', format_parsetime($params['duration']), ' sec</td>';
	}

	/**
	 * Unpack a single element from the json-repsonse.
	 *
	 * @param string|URL $url
	 * @param string $element  The data node
	 * @return mixed
	 */
	private function single($url, $element) {
		$response = $this->fetchData($url);
		return $response[$element];
	}

	/**
	 * Unpack the elements from the json-repsonse.
	 *
	 * @param string|URL $url
	 * @param string $element  The data node
	 * @return array
	 */
	private function multiple($url, $element) {
		$response = $this->fetchData($url);
		$data = array();
		foreach ($response as $item) {
			$data[] = $item[$element];
		}
		return $data;
	}

	/**
	 * Fetch and unpack jsonstring from the Beanstalk api
	 * @param string|URL $url
	 * @return mixed
	 */
	private function fetchData($url) {
		$now = microtime(true);
		$responseText = file_get_contents($url);
		$this->logger->append(substr($url, strpos($url, '/api/')), array(// Log the request (without the credentials)
			'duration' => microtime(true) - $now
		));

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
		$url->user = $this->username;
		$url->pass = $this->password;
		$url->query = $params;
		return $url;
	}

}

?>
