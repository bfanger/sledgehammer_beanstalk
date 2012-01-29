<?php

namespace SledgeHammer;

/**
 * BeanstalkBackend
 */
class BeanstalkRepositoryBackend extends RepositoryBackend {

	public $identifier = 'beanstalk';

	/**
	 * @var BeanstalkClient
	 */
	private $client;

	function __construct($account, $user = null, $password = null) {
		$this->client = new BeanstalkClient($account, $user, $password);
		$this->configs = array(
			'Repository' => new ModelConfig('Repository', array(
				'class' => false,
				'properties' => array(
					'id' => 'id',
					'name' => 'name',
					'title' => 'title',
					'repository_url' => 'url',
					'vcs' => 'vcs',
					'created_at' => 'created',
					'last_commit_at' => 'lastCommit',
					'branches' => 'branches',
					'tags' => 'tags',
				),
				'belongsTo' => array(
					'account' => array(
						'model' => 'Account',
						'reference' => 'account_id',
					)
				),
				'hasMany' => array(
					'releases' => array(
						'model' => 'Release',
						'reference' => 'repository.id'
					),
				),
				'backendConfig' => array(
					'type' => 'repository',
				),
			)),
			'Release' => new ModelConfig('Release', array(
				'properties' => array(
					'id' => 'id',
					'name' => 'name',
				),
				'belongsTo' => array(
					'repository' => array(
						'model' => 'Repository',
						'reference' => 'repository_id'
					),
				),
				'backendConfig' => array(
					'type' => 'release',
				),
			)),
			'Account' => new ModelConfig('Account', array(
				'properties' => array(
					'id' => 'id',
					'name' => 'name',
					'third_level_domain' => 'subdomain',
				),
				'belongsTo' => array(
					'owner' => array(
						'model' => 'User',
						'reference' => 'owner_id',
					)
				),
				'backendConfig' => array(
					'type' => 'account',
				),
			)),
			'User' => new ModelConfig('User', array(
				'properties' => array(
					'id' => 'id',
					'first_name' => 'firstname',
					'last_name' => 'lastname',
					'email' => 'email',
					'owner' => 'isOwner',
					'admin' => 'isAdmin'
				),
				'belongsTo' => array(
					'account' => array(
						'model' => 'Account',
						'reference' => 'account_id',
					)
				),
				'backendConfig' => array(
					'type' => 'user',
				),
			))
		);
	}

	function get($id, $config) {
		switch ($config['type']) {

			case 'repository':
				$repository = $this->client->getRepository($id);
				$repository['branches'] = $this->client->getBranchesFor($id);
				$repository['tags'] = $this->client->getTagsFor($id);
				return $repository;

			case 'account':
				return $this->client->getAccount($id);

			case 'user':
				return $this->client->getUser($id);

			default:
				throw new InfoException('Unsupported config', $config);
		}
	}

	function all($config) {
		if (in_array($config['type'], array('repository', 'release')) === false) {
			throw new InfoException('Unsupported config', $config);
		}
		return new BeanstalkCollection($this->client, $config['type']);
	}

}

?>
