# oc-wallet-plugin
Wallet plugin for OctoberCMS

## Installation

1. [**Download**](https://github.com/octobroid/oc-wallet-plugin/archive/master.zip) this plugin and put to plugins directory (`plugins/octobro/wallet`).
2. Run `composer update` on your project root directory.

> Tips: if you want to follow this plugin, you can use this plugin as a submodule on your git project.

## Usage

This plugin is used for your model. You should create your model for your application first.

### Create Your Model

```
php artisan create:model Foo.Bar ModelName
```

In your `Plugin.php` file, we recommend you to put `Octobro.Wallet` as plugin dependency.

```php
class Plugin extends PluginBase
{
	public $require = ['Octobro.Wallet'];
	
```

Add `wallet_amount` column to your model using migration.

```php
<?php namespace Foo\Bar\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddWalletAmountToModelsTable extends Migration
{
    public function up()
    {
        Schema::table('foo_bar_models', function(Blueprint $table) {
            $table->decimal('wallet_amount', 12, 2)->unsigned()->default(0);
        });
    }

    public function down()
    {
        Schema::table('foo_bar_models', function(Blueprint $table) {
            $table->dropColumn('wallet_amount');
        });
    }
}
```

## Extending Plugins

In this example we use `User.php` model from `RainLab.User`.

### Adding Includes

```php
// Add this on your plugin boot() method

User::extend(function($model) {
    // For example it has wallet logs relation
    $model->morphMany['wallet_logs'] = [
        'Octobro\Wallet\Models\Log',
        'name' => 'owner'
    ];
});

// This config implements wallet logs relation list in User model
UsersController::extend(function($controller) {
    // Implement behavior if not already implemented
    if (!in_array('Backend.Behaviors.RelationController', $controller->implement) && !in_array('Backend\Behaviors\RelationController', $controller->implement)) {
        $controller->implement[] = 'Backend.Behaviors.RelationController';
    }

    // Define property if not already defined
    if (!isset($controller->relationConfig)) {
        $controller->addDynamicProperty('relationConfig');
    }

    // Splice in configuration safely
    $myConfigPath = __DIR__ . '/../../octobro/wallet/config/wallet_logs_relation.yaml';

    $controller->relationConfig = $controller->mergeConfig(
        $controller->relationConfig,
        $myConfigPath
    );
});

// This config extends form fields for wallet
UsersController::extendFormFields(function($form, $model, $context) {
    if (! $model instanceof \RainLab\User\Models\User) return;
    $configFile = __DIR__ . '/../../octobro/wallet/config/wallet_fields.yaml';
    $config = Yaml::parse(File::get($configFile));
    $form->addTabFields($config);
});
```

## License

The OctoberCMS platform is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).