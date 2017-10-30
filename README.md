AliceBundle
===========

A [Symfony](http://symfony.com) bundle to manage fixtures with [nelmio/alice](https://github.com/nelmio/alice) and
[fzaninotto/Faker](https://github.com/fzaninotto/Faker).

The database support is done in [FidryAliceDataFixtures](https://github.com/theofidry/AliceDataFixtures). Check this
project to know which database/ORM is supported.

**Warning: this doc is behind updated for HautelookAliceBundle 2.0. If you want to check the documentation for 1.x, head
[this way](https://github.com/hautelook/AliceBundle/tree/1.x).**

[![Package version](https://img.shields.io/packagist/vpre/hautelook/alice-bundle.svg?style=flat-square)](https://packagist.org/packages/hautelook/alice-bundle)
[![Build Status](https://img.shields.io/travis/hautelook/AliceBundle/master.svg?style=flat-square)](https://travis-ci.org/hautelook/AliceBundle?branch=master)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/d93a3fc4-3fe8-4be3-aa62-307f53898199.svg?style=flat-square)](https://insight.sensiolabs.com/projects/d93a3fc4-3fe8-4be3-aa62-307f53898199)
[![Dependency Status](https://www.versioneye.com/user/projects/55d26478265ff6001a000084/badge.svg?style=flat)](https://www.versioneye.com/user/projects/55d26478265ff6001a000084)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/hautelook/AliceBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/hautelook/AliceBundle/?branch=master)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/hautelook/AliceBundle.svg?b=master&style=flat-square)](https://scrutinizer-ci.com/g/hautelook/AliceBundle/?branch=master)
[![Slack](https://img.shields.io/badge/slack-%23alice--fixtures-red.svg?style=flat-square)](https://symfony-devs.slack.com/shared_invite/MTYxMjcxMjc0MTc5LTE0OTA3ODE4OTQtYzc4NWVmMzRmZQ)

## Documentation

1. [Install](#installation)
1. [Basic usage](#basic-usage)
1. [Advanced usage](doc/advanced-usage.md)
    1. [Enabling databases](doc/advanced-usage.md#enabling-databases)
    1. [Environment specific fixtures](doc/advanced-usage.md#environment-specific-fixtures)
    1. [Fixtures parameters](doc/advanced-usage.md#fixtures-parameters)
        1. [Alice parameters](doc/advanced-usage.md#alice-parameters)
        1. [Application parameters](doc/advanced-usage.md#application-parameters)
    1. [Use service factories](doc/advanced-usage.md#use-service-factories)
    1. [Load fixtures in a specific order](doc/advanced-usage.md#load-fixtures-in-a-specific-order)
        1. [Load fixtures in a specific order](doc/advanced-usage.md#ordering-the-files-found)
        1. [Persisting the classes in a specific order](doc/advanced-usage.md#persisting-the-classes-in-a-specific-order)
1. [Custom Faker Providers](doc/faker-providers.md)
1. [Custom Alice Processors](doc/alice-processors.md)
1. [Resources](#resources)

Other references:

* [Knp University screencast](https://knpuniversity.com/screencast/alice-fixtures)


## Installation

Example of installation:

```bash
# If you are using Symfony standard edition, you can skip this step
composer require doctrine/doctrine-bundle doctrine/orm:^2.5

composer require --dev hautelook/alice-bundle:^2.0@beta \
  nelmio/alice:^3.0@beta \
  theofidry/alice-data-fixtures:^1.0@beta \
  doctrine/data-fixtures
```

Explanation: HautelookAliceBundle uses [FidryAliceDataFixtures](https://github.com/theofidry/AliceDataFixtures) for the
persistence layer. As FidryAliceDataFixtures is compatible with different databases/ORM, one cannot be installed by
default. In the example above, we are using Doctrine ORM which requires
`doctrine/orm doctrine/orm-bundle doctrine/data-fixtures`.

Then, enable the bundle by updating your `app/AppKernel.php` file to enable the bundle:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
        // ...
        new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
    ];
    
    if (in_array($this->getEnvironment(), ['dev', 'test'])) {
        //...
        $bundles[] = new Nelmio\Alice\Bridge\Symfony\NelmioAliceBundle();
        $bundles[] = new Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle();
        $bundles[] = new Hautelook\AliceBundle\HautelookAliceBundle();
    }

    return $bundles;
}
```

Configure the bundle to your needs (example with default values):

```yaml
# app/config/config_dev.yml

hautelook_alice:
    fixtures_path: 'Resources/fixtures/orm' # Path to which to look for fixtures relative to the project directory or the bundle path.
```


## Basic usage

Assuming you are using [Doctrine](http://www.doctrine-project.org/projects/orm.html), make sure you
have the [`doctrine/doctrine-bundle`](https://github.com/doctrine/DoctrineBundle) and
[`doctrine/data-fixtures`](https://github.com/doctrine/data-fixtures) packages installed.

Then create a fixture file in `app/Resources/fixtures/orm`:

```yaml
# app/Resources/fixtures/orm/dummy.yml

AppBundle\Entity\Dummy:
    dummy_{1..10}:
        name: <name()>
        related_dummy: '@related_dummy*'
```

```yaml
# app/Resources/fixtures/orm/related_dummy.yml

AppBundle\Entity\RelatedDummy:
    related_dummy_{1..10}:
        name: <name()>
```

Then simply load your fixtures with the doctrine command `php bin/console hautelook:fixtures:load`.

If you want to load the fixtures of a bundle only, do `php bin/console hautelook:fixtures:load -b MyFirstBundle -b MySecondBundle`.

[See more](#documentation).<br />
Next chapter: [Advanced usage](doc/advanced-usage.md)


## Resources

* Behat extension: [AliceBundleExtension](https://github.com/theofidry/AliceBundleExtension)
* Bundle for generating AliceBundle compatible fixtures directly from Doctrine entities: [AliceGeneratorBundle](https://github.com/trappar/AliceGeneratorBundle)
* [Upgrade guide](UPGRADE.md)
  * [Upgrade from 0.X to 1.X](UPGRADE.md#from-0x-to-1x)
* [Changelog](CHANGELOG.md)


## Credits

This bundle was originaly developped by [Baldur RENSCH](https://github.com/baldurrensch) and [HauteLook](https://github.com/hautelook). It is now maintained by [Théo FIDRY](https://github.com/theofidry).

[Other contributors](https://github.com/hautelook/AliceBundle/graphs/contributors).


## License

[![license](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE)
