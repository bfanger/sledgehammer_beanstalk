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
	public $logger;

	function __construct($account, $username = null, $password = null) {
		$this->account = $account;
		$this->username = $username;
		$this->password = $password;
		$this->logger = new Logger(array(
			'identifier' => 'Beanstalk',
			'columns' => array('Request', 'Duration'),
			'plural' => 'requests',
			'singular' => 'request',
			'renderer' => array($this, 'renderLog')
		));
	}

	/**
	 * Fetch all repositories. (in the account the user has access to)
	 * @return array
	 */
	function getRepositories() {
		return $this->get('repositories');
	}

	/**
	 * Fetch info about a repository.
	 * @param string|int $id  ID or Repository URL ($account.beanstalkapp.com/$id)
	 * @return array
	 */
	function getRepository($id) {
		return $this->get('repositories/'.$id);
	}

	/**
	 * Fetch info about the account
	 * @param int $id
	 * @return array
	 */
	function getAccount($id) {
		return $this->get('accounts/'.$id);
	}

	/**
	 * Fetch all users in the account.
	 * @return array
	 */
	function getUsers() {
		return $this->get('users');
	}

	/**
	 * Fetch info about a user.
	 * @param int $id
	 * @return array
	 */
	function getUser($id) {
		return $this->get('users/'.$id);
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
			return new BeanstalkPagedResult($this, 'changesets', $options);
		}
		return $this->get('changesets', $options);
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
			return new BeanstalkPagedResult($this, 'changesets/repository', $options);
		}
		return $this->get('changesets/repository', $options);
	}

	/**
	 * @param int|string $repository The id or name of the repository
	 * @return array
	 */
	function getReleasesFor($repository) {
		return $this->get($repository.'/releases');
	}

	/**
	 * @param int|string $repository The id or name of the repository
	 * @return array
	 */
	function getTagsFor($repository) {
		return $this->get('repositories/'.$repository.'/tags');
	}

	/**
	 * @param int|string $repository The id or name of the repository
	 * @return array
	 */
	function getBranchesFor($repository) {
		return $this->multiple($this->buildUrl('repositories/'.$repository.'/branches'), 'branch');
	}

	/**
	 * Perform a GET request to the Beanstalk REST API.
	 *
	 * @param string $path Example "repositories/12345"
	 * @param string $parameters Example array('per_page' => 50)
	 */
	function get($path, $parameters = array()) {
		$url = new URL('https://'.$this->account.'.beanstalkapp.com/api/'.$path.'.json');
		$url->user = $this->username;
		$url->pass = $this->password;
		$url->query = $parameters;
		$now = microtime(true);
		$request = new cURL(array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR => true,
			CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Connection: Keep-Alive',
				'Keep-Alive: 60'
			),
			CURLOPT_USERAGENT => 'Sledgehammer BeanstalkClient',
			CURLOPT_VERBOSE => true
		));
		$response = $request->getContent();
		if ($request->getInfo(CURLINFO_CONTENT_TYPE) !== 'application/json; charset=utf-8') {
			throw new InfoException('Invalid response for "'.$path.'"', array('Response' => $response));
		}
		$url->user = null; // don't log username / password
		$url->pass = null;
		$this->logger->append((string) $url, array(
			'parameters' => $parameters,
			'duration' => microtime(true) - $now
		));
		$result = Json::decode($response, true);
		if (count($result) === 0) {
			return $result;
		}
		$type = key($result);
		if ($type !== 0) { // single record?
			return $result[$type];;
		}
		// multiple records
		$records = array();
		$type = key($result[0]);
		foreach ($result as $item) {
			$records[] = $item[$type];
		}
		return $records;
	}

	/**
	 * Render a log entry.
	 *
	 * @param string $url
	 * @param array $params
	 */
	function renderLog($url, $params) {
		echo '<td><a href="', $url, '" target="_blank">', HTML::escape(substr($url, strpos($url, '/', 10))), '</a></td>';
		echo '<td>', format_parsetime($params['duration']), ' sec</td>';
	}
}

?>
