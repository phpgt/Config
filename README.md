# Manage configuration with ini files and environment variables.

Manage your project configuration by defining defaults, that can be overridden by ini files and environment variables.

Also provides functionality for generating ini files from the CLI.

Documentation: https://php.gt/docs/config

***

<a href="https://github.com/PhpGt/Config/actions" target="_blank">
	<img src="https://badge.status.php.gt/config-build.svg" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Config" target="_blank">
	<img src="https://badge.status.php.gt/config-quality.svg" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Config" target="_blank">
	<img src="https://badge.status.php.gt/config-coverage.svg" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/Config" target="_blank">
	<img src="https://badge.status.php.gt/config-version.svg" alt="Current version" />
</a>
<a href="http://www.php.gt/config" target="_blank">
	<img src="https://badge.status.php.gt/config-docs.svg" alt="PHP.Gt/Config documentation" />
</a>

## Example usage - loading project configuration:

A project's configuration can be split across multiple files. The following example shows how a secret can be supplied through the environment, which is used to override the default value defined within config.ini, and also shows how other named config files can be used.

nginx.conf:

```
location ~ \.php$ {
	fastcgi_pass	unix:/var/run/php/php7.1-fpm.sock;
	fastcgi_param	database_password	super-secret-passw0rd;
	include		fastcgi_params;
}
```

config.ini:

```ini
[app]
namespace = MyApp
debug = true
logging = verbose

[database]
host = db.example.com
schema = local_shop
username = admin
password = admin_pass

[shopapi]
key = jungfnyyguvffubhgvat
secret = guvfvfnybpnyfubcgurerfabguvatsbelbhurer
```

config.dev.ini:

```ini
[database]
host = localhost
```

example.php:

```php
// Load config.ini
$config = new Config("/path/to/project");

// Note that the database password is overriden in the environment (from nginx)
// and the host is overridden by the development ini file.
echo $config->get("database.host");		// localhost
echo $config->get("database.port");		// 6612
echo $config->get("database.password");		// super-secret-passw0rd
```

## Example usage - generating configuration files:

Sometimes it's useful to generate config files on-the-fly, such as from Continuous Integration scripts. Below shows a quick example of how to generate a `config.deploy.ini` file with a few key-values that will override the default.

```
vendor/bin/config-generate deploy "shopapi.key=test-api-key" "database.schema=local_shop_$BRANCH_NAME"
```

The above command will create a `config.deploy.ini` file (note the first argument of "deploy") and provide overrides for two ini keys using dot notation. Note that because this command will be run within a continuous integration setting, we are expecting there to be a $BRANCH_NAME variable set for us, allowing us to use a schema name containing the current branch. 

# Proudly sponsored by

[JetBrains Open Source sponsorship program](https://www.jetbrains.com/community/opensource/)

[![JetBrains logo.](https://resources.jetbrains.com/storage/products/company/brand/logos/jetbrains.svg)](https://www.jetbrains.com/community/opensource/)
