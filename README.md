ActiveRecord
==============

Modern ActiveRecord implementation for PHP 5.4+

[ActiveRecord is an architectural pattern](https://en.wikipedia.org/wiki/Active_record_pattern) for adding
database awareness and [CRUD](https://en.wikipedia.org/wiki/CRUD) functionality to domain objects. It is 
simple to use, easy to maintain and performant when used together with well-designed userland code.

The ActiveRecord pattern has been discussed, debated, critiqued and praised for decades. You can learn more
about when to use it, when not to use it and what are some of the caveats in the following document: 
[docs/discussion.md](docs/discussion.md).

### Requirements

  * PHP 5.4.3 or newer
  * Database Abstraction Layer, one of the following:
    * [Zend Framework 2.2 Zend\Db](https://github.com/zendframework/zf2) or
    * [Doctrine DBAL 2.3+](https://github.com/doctrine/dbal)
    * 

### Installation using Composer

 1. Inside your app directory run `composer require thinkscape/activerecord:dev-master`
 2. Make sure you are using composer autoloader: `include "vendor/autoload.php";`
 3. Follow [quick start](docs/quickstart.md) instructions.

### Manual installation
 
 1. Obtain the source code with by either:
   * cloning git [project from github](https://github.com/Thinkscape/ActiveRecord.git), or
   * downloading and extracting [source package](https://github.com/Thinkscape/ActiveRecord/archive/master.zip).
 2. Set up class autoloading by either:
   * using the provided autoloader: `require "init_autoload.php";`, or
   * adding `src` directory as namespace `Thinkscape\ActiveRecord` to your existent autoloader.
 3. Follow [quick start](docs/quickstart.md) instructions.


