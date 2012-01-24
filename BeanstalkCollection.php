<?php
/**
 * BeanstalkCollection
 */

namespace SledgeHammer;

class BeanstalkCollection extends Collection {

	private $url;
	private $element;

	function __construct($url, $element) {
		$this->url = $url;
		$this->element = $element;
	}

	function rewind() {
		if ($this->data === null) {
			$this->dataToArray();
		}
		return parent::rewind();
	}

	protected function dataToArray() {
		if ($this->data === null) {
			$responseText = file_get_contents($this->url);
			$response = json_decode($responseText, true);
			if ($response) {
				$this->data = array();
				foreach ($response as $item) {
					$this->data[] = $item[$this->element];
				}
			} else {
dump($this->url);
				dump($responseText);
				throw new \Exception('Failed');
			}
		}
	}

}

?>
