<?php
/**
 * BeanstalkPagedResult
 */
namespace Sledgehammer;
/**
 * Iterator that will fetch all pages for a paged API call.
 * @todo Prefetch the next page in the background.
 *
 * @package Beanstalk
 */
class BeanstalkPagedResult extends Object implements \Iterator {

	/**
	 * @var BeanstalkClient
	 */
	private $client;

	/**
	 * path of the API call.
	 * @var string
	 */
	private $path;

	/**
	 * @var array
	 */
	private $parameters;

	/**
	 * Active page.
	 * @var int
	 */
	private $page = 1;

	/**
	 * Resultset of the API calls.
	 * @var type
	 */
	private $results = array();

	/**
	 * @var bool
	 */
	private $isLastPage = false;

	/**
	 * Constructor
	 * @param BeanstalkClient $client
	 * @param string $path
	 * @param array $parameters
	 */
	function __construct($client, $path, $parameters = array()) {
		$this->client = $client;
		$this->path = $path;
		$this->parameters = $parameters;
		if (empty($this->parameters['per_page'])) {
			$this->parameters['per_page'] = 30;
		}
	}

	/**
	 * Iterator::current()
	 * @return mixed
	 */
	function current() {
		return current($this->results[$this->page]);
	}

	/**
	 * Iterator::key()
	 * @return int
	 */
	function key() {
		$base = ($this->page - 1) * $this->parameters['per_page'];
		$key = key($this->results[$this->page]);
		if ($key !== null) {
			return $base + $key;
		}
	}

	/**
	 * Iterator::next()
	 * @return void
	 */
	function next() {
		$next = next($this->results[$this->page]);
		if ($next === false && key($this->results[$this->page]) === null && $this->isLastPage === false) { // End of page?
			$this->page++;
			$this->parameters['page'] = $this->page;
			$this->results[$this->page] = $this->client->get($this->path, $this->parameters);
			$this->isLastPage = (count($this->results[$this->page]) != $this->parameters['per_page']); // Are there less records than the page
		}
	}

	/**
	 * Iterator::rewind()
	 * @return void
	 */
	function rewind() {
		if (count($this->results) === 0) {
			$this->parameters['page'] = 1;
			$this->results[1] = $this->client->get($this->path, $this->parameters);
		} else {
			foreach (array_keys($this->results) as $i) {
				reset($this->results[$i]);
			}
		}
		$this->page = 1;
		$this->isLastPage = (count($this->results[1]) != $this->parameters['per_page']); // Are there less records than the page
	}

	/**
	 * Iterator::valid()
	 * @return bool
	 */
	function valid() {
		return (key($this->results[$this->page]) !== null);
	}

}

?>