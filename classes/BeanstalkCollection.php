<?php
/**
 * BeanstalkCollection
 */

namespace SledgeHammer;

class BeanstalkCollection extends Collection {

	/**
	 * @var BeanstalkClient
	 */
	private $client;
	private $type;
	private $repository;

	function __construct($client, $type) {
		$this->client = $client;
		$this->type = $type;
	}

	function rewind() {
		if ($this->data === null) {
			$this->dataToArray();
		}
		return parent::rewind();
	}

	protected function dataToArray() {
		if ($this->data === null) {
			switch ($this->type) {

				case 'release':
					$this->data = $this->client->getReleasesFor($this->repository);
					break;

				case 'repository':
					$this->data = $this->client->getRepositories();
					foreach ($this->data as $index => $repository) {
						$this->data[$index]['branches'] = $this->client->getBranchesFor($repository['id']);
						$this->data[$index]['tags'] = $this->client->getTagsFor($repository['id']);
					}
					break;

				default:
					throw new \Exception('Type: "'.$this->type.'" not supported');
			}
		}
	}

	function where($conditions) {
		if (is_array($conditions) && $this->repository === null) {
			foreach ($conditions as $field => $value) {
				if ($field === 'repository_id') {
					unset($conditions[$field]);
					$this->repository = $value;
					$collection = parent::where($conditions);
					$this->repository = null;
					return $collection;
				}
			}
		}
		return parent::where($conditions);
	}

}

?>
