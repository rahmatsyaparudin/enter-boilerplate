<p align="center">
    <a href="https://enterkomputer.com" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/176936301" height="100px">
    </a>
    <h1 align="center">Enterkomputer API Boilerplate</h1>
    <br>
</p>

Skeleton API services for project Enterkomputer using Yii2 Framework.

REQUIREMENTS
------------

The minimum requirement by this project template that your Web server supports PHP 7.4.


INSTALLATION
------------

### Just Download from an Archive File



### Install from an Archive File

Extract the archive file downloaded from [yiiframework.com](https://github.com/ybsisgood/enterkomputer-api-boilerplate) to localhost.

Set extraCookie validation key in `config/params.php` file to add some random secret string:

```php
return [
    'extraCookies' => '<extra random string>',
],
```

CONFIGURATION
-------------

### Database

Edit the file `config/db.php` with real data, for example:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    'username' => 'root',
    'password' => '1234',
    'charset' => 'utf8',
];
```

### Last Setup
Update dependencies with Composer 

    ```
    composer install  
    ```
