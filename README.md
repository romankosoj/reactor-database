# Database driver

Wrapped PDO

## Installation with composer:

```javascript
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/toecto/reactor-database.git"
        }
    ],
    "require": {
        "reactor/database": "dev-master"
    }
  ```
## Usage

```php

$db = new \\Reactor\\Database\\PDO\\Connection(
        "mysql:dbname=test;host=localhost",
        "db.user",
        "db.pass"
);

$catalog = $db->sql("select * from orders");

foreach($catalog as $line) {
    print_r($line);
}

$catalog = $db->sql("select * from orders");
foreach($catalog->matr('order_id') as $key => $line) {
    echo $key;
    print_r($line);
}

$catalog = $db->sql("select * from orders");
foreach($catalog->matr('order_id', 'name') as $key => $line) {
    echo "$key = $line";
}


echo $db->sql("select * from orders where order_id=?", 1)->line('name');
echo $db->sql("select * from orders where order_id=?", null)->exec(1)->line('name');


$catalog = $db->sql("select * from orders where order_id=?", null);
print_r($catalog->exec(1)->line());
print_r($catalog->exec(2)->line('name'));
print_r($catalog->exec(3)->line());
print_r($catalog->getStats());

$catalog = $db->sql("select * from orders where date=?", null);
foreach($catalog->exec(date()) as $order) {
    print_r($order);
}

```
