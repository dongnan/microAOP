microAOP - Aspect-Oriented library for PHP
-----------------
microAOP is A mirco & powerful AOP library for PHP,Only less than 300 lines of code,but has many useful features.

Installation
------------

microAOP library can be installed with composer. Installation is quite easy:

1. Download the library using composer
2. Create an application model
3. Create an aspect
4. Bind the aspect to an instance of the model

### Step 1: Download the library using composer

Ask composer to download the microAOP with its dependencies by running the command:

``` bash
$ composer require dongnan/microaop
```

Composer will install the framework to your project's `vendor/dongnan/microaop` directory.

### Step 2: Create an application model

``` php
<?php
class Model
{

    public function save()
    {
        echo __METHOD__ . ' has been executed' . PHP_EOL;
    }

}
```

### Step 3: Create an aspect

``` php
<?php
class Aspect
{

    public function saveBefore($params)
    {
        echo '------------------------------------------' . PHP_EOL;
        echo __METHOD__ . ' has been executed' . PHP_EOL;
    }

    public function saveAfter($params)
    {
        echo '------------------------------------------' . PHP_EOL;
        echo __METHOD__ . ' has been executed' . PHP_EOL;
    }

}
```

### Step 4: Bind the aspect to an instance of the model

``` php
<?php

use microAOP\Proxy;
use yournamespace\Model;
use yournamespace\Aspect;

$model = new Model();

//Just bind it
Proxy::__bind__($model, new Aspect());

$model->save();

```

Output:
```
------------------------------------------
Aspect::saveBefore has been executed
Model::save has been executed
------------------------------------------
Aspect::saveAfter has been executed

```
