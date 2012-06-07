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
	 * URL of the paged API call.
	 * @var URL
	 */
	private $url;

	/**
	 * The name of the wrapper element.
	 * @var string
	 */
	private $element;

	/**
	 * Active page.
	 * @var int
	 */
	private $page = 1;

	/**
	 * Results per page (max 30)
	 * @var int
	 */
	private $perPage = 30;

	/**
	 * Resultset of the API calls.
	 * @var type
	 */
	private $results = array();

	/**
	 *
	 * @var bool
	 */
	private $isLastPage = false;

	/**
	 * Constructor
	 * @param URL $url URL of the paged API call.
	 * @param string $element The name of the wrapper element.
	 */
	function __construct($url, $element) {
		$this->url = $url;
		$this->element = $element;
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
		$base = ($this->page - 1) * $this->perPage;
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
			$this->results[$this->page] = $this->fetchPage($this->page);
			$this->isLastPage = (count($this->results[$this->page]) != $this->perPage); // Are there less records than the page
		}
	}

	/**
	 * Iterator::rewind()
	 * @return void
	 */
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

	/**
	 * Iterator::valid()
	 * @return bool
	 */
	function valid() {
		return (key($this->results[$this->page]) !== null);
	}

	/**
	 * Execute the API call for the given page and unpack the result.
	 *
	 * @param int $page
	 * @return array Resultset
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