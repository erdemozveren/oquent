# Oquent
Orientdb Eloquent-like driver for Laravel 5 using the binary protocol [PhpOrient](https://github.com/Ostico/PhpOrient).  (Not Ready For Production)

## What is Oquent ?
Oquent goal is lets you use OrientDB as usual Eloquent.It's on development and should not be used in production for now.

## Requirements
   * Laravel 5.5 or above
   * Orientdb Server 2.2

## Features
- [x] You can use Larvel's Authentication 
- [x] CRUD (Create, read, update, delete) 
- [x] Paginate Model (need to be tested,but working for now)
- [x] Fetchplan (See [OrientDb Fetching Strategies Docs](https://orientdb.com/docs/2.2/Fetching-Strategies.html))
- [ ] Edges / Relations

## Database Configuration

Open `config/database.php`
make `orientdb` your default connection:

```php
'default' => 'orientdb',
```

And optionally, if you want to use orientdb as a secondary connection
```php
'default_nosql' => 'orientdb',
```

Add the connection defaults:

```php
'connections' => [
    'orientdb' => [
            'driver'=> 'orientdb',
            'host' => '192.168.1.5', // Host 
            'port' => 2424, // We Use Binary so do not change it if not necessary 
            'database' => 'DatabaseName',
            'username' => 'root',
            'password' => 'passwordforuser'
    ],
]
```
