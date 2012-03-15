<?php
namespace SledgeHammer;
/**
 * BeanstalkPagedResult, An iterator that will fetch all records in a paged result.
 * The iterator sends the request for the next page when it is needed.
 *
 * @package Beanstalk
 */
class BeanstalkPagedResult extends Object implements \Iterator {

	private $url;
	private $element;
	private $page = 1;
	private $perPage = 30;
	private $results = array();
	private $isLastPage = false;

	function __construct($url, $element) {
		$this->url = $url;
		$this->element = $element;
	}

	function current() {
		return current($this->results[$this->page]);
	}

	function key() {
		$base = ($this->page - 1) * $this->perPage;
		$key = key($this->results[$this->page]);
		if ($key !== null) {
			return $base + $key;
		}
	}

	function next() {
		$next = next($this->results[$this->page]);
		if ($next === false && key($this->results[$this->page]) === null && $this->isLastPage === false) { // End of page?
			$this->page++;
			$this->results[$this->page] = $this->fetchPage($this->page);
			$this->isLastPage = (count($this->results[$this->page]) != $this->perPage); // Are there less records than the page
			return false;
		}
	}

	function rewind() {
		if (count($this->results) === 0) {
			$this->results[1] = $this->fetchPage(1);
		} else {
			foreach (array_keys($this->results) as $i) {
				reset($this->results[$i]);
			}
		}
		$this->page = 1;
		$this->isLastPage = (count($this->results[1]) != $this->perPage); // Are there less records than the page
	}

	function valid() {
		return (key($this->results[$this->page]) !== null);
	}

	/**
	 * Retrieve the data
	 * @param int $page
	 * @throws InfoException
	 */
	private function fetchPage($page) {
		$this->url->query['page'] = $page;
		$this->url->query['per_page'] = $this->perPage;
		$responseText = file_get_contents($this->url);
		if ($responseText === false) {
			throw new InfoException('Beanstalk API Request failed', (string) $url);
		}
		if ($responseText === "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<releases type=\"array\"/>\n") {
			return array();
		}
		$data = Json::decode($responseText, true);
		$result = array();
		foreach ($data as $item) {
			$result[] = $item[$this->element];
		}
		return $result;
	}

}

?>