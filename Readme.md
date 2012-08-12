# Sledghammer Beanstalk Client

Access repository information via the Beanstalk REST API.

## Usage

Using the BeanstalkClient directly gives control about which requests are fetched.

```php
// Init
$beanstalk = new BeanstalkClient('subdomain', 'username', 'p4ssw0rd');
// Fetch commits
$commits = $beanstalk->getChangesetFor('my-repository');
```

## Recommended usage

Using the Repository OOP interface allows autocompletion, chaining relations and filtering and sorting.
And because the repository will use the same instance per unique id, it automaticly prevents duplicate requests.

```php
// Init
$beanstalk = new BeanstalkClient('subdomain', 'username', 'p4ssw0rd');
$backend = new BeanstalkRepositoryBackend($beanstalk);
$repo = getRepository();
$repo->registerBackend($backend);
// Fetch commits
$commits = $repo->getRepository('my-repository')->commits->where(array('author' => 'Bob Fanger'))->toArray();
```
