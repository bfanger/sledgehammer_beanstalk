<?php
/**
 * BeanstalkBackend
 */

namespace SledgeHammer;

class BeanstalkRepositoryBackend extends RepositoryBackend {

	public $identifier = 'beanstalk';

	private $account;
	private $user;
	private $password;

	function __construct($account, $user = null, $password = null) {
		$this->account = $account;
		$this->user = $user;
		$this->password = $password;

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
				),
				'belongsTo' => array(
					'account' => array(
						'model' => 'Account',
						'reference' => 'account_id',
					)
				),
				'backendConfig' => array(
					'path' => 'repositories',
					'element' => 'repository',
				),
			)),
			'Release' => new ModelConfig('Release', array(
				'properties' => array(
					'id' => 'id',
					'name' => 'name',
				),
				'backendConfig' => array(
					'path' => 'releases',
					'element' => 'release',
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
					'path' => 'accounts',
					'element' => 'account',
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
					'path' => 'users',
					'element' => 'user',
				),
			))
		);
	}

	function get($id, $config) {
		$url = $this->getUrl($config['path'].'/'.rawurlencode($id));
		$responseText = file_get_contents($url);
		$response = json_decode($responseText, true);
		return $response[$config['element']];
	}

	function all($config) {
		return new BeanstalkCollection($this->getUrl($config['path']), $config['element']);
	}

	/**
	 *
	 * @param string $path
	 * @param array $params
	 * @return \SledgeHammer\URL
	 */
	private function getUrl($path, $params = array()) {
		$url = new URL('http://'.$this->account.'.beanstalkapp.com/api/'.$path.'.json');
		$url->user = $this->user;
		$url->pass = $this->password;
		$url->query = $params;
		return $url;
//
	}
}

?>
