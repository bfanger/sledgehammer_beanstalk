<?php
/**
 * BeanstalkBackend
 */
namespace Sledgehammer;
/**
 * @package Beanstalk
 */
class BeanstalkRepositoryBackend extends RepositoryBackend {

	public $identifier = 'beanstalk';

	/**
	 * @var BeanstalkClient
	 */
	private $client;

	/**
	 * Constructor
	 * @param BeanstalkClient $beanstalkClient
	 */
	function __construct($beanstalkClient) {
		$this->client = $beanstalkClient;
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
//					'branches' => 'branches',
//					'tags' => 'tags',
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
					'commits' => array(
						'model' => 'Commit',
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
			'Commit' =>  new ModelConfig('Commit', array(
				'properties' => array(
					'revision' => 'id',
					"hash_id" => "hash",
					"message" => "message",
					"time" => "created",
					"author" => 'author',
					"email" => "email",
					"changed_files" => 'files',
					"changed_dirs" => 'folders',
					"changed_properties"=> 'properties',
					"too_large" => 'isTooLarge',
				),
				'id' => array('revision'),
				'belongsTo' => array(
					'repository' => array(
						'model' => 'Repository',
						'reference' => 'repository_id'
					),
					'account' => array(
						'model' => 'Account',
						'reference' => 'account_id'
					),
					'user' => array(
						'model' => 'User',
						'reference' => 'user_id'
					),

				),
				'backendConfig' => array(
					'type' => 'commit',
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
//				$repository['branches'] = $this->client->getBranchesFor($id);
//				$repository['tags'] = $this->client->getTagsFor($id);
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
		switch ($config['type']) {

			case 'repository':
				return $this->client->getRepositories();

			case 'commit':
				return $this->client->getChangesets();

			default:
				throw new InfoException('Unsupported config', $config);
		}
	}

	function related($relation, $id) {
		if ($relation['reference'] != 'repository.id') {
			return parent::related($relation, $id);
		}
		$config = $this->configs[$relation['model']]->backendConfig;

		switch ($config['type']) {

			case 'commit':
				return $this->client->getChangesetsFor($id);

			case 'release':
				return $this->client->getReleasesFor($id);

			default:
				throw new InfoException('Unsupported config', $config);
		}
	}
}

?>
