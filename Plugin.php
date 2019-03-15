<?php namespace Octobro\Wallet;

use Yaml;
use File;
use Backend;
use System\Classes\PluginBase;
use RainLab\User\Models\User;
use Illuminate\Foundation\AliasLoader;
use RainLab\User\Controllers\Users as UsersController;

/**
 * Wallet Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Wallet',
            'description' => 'No description provided yet...',
            'author'      => 'Octobro',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        User::extend(function($model) {
            $model->hasMany['wallet_logs'] = [
                'Octobro\Wallet\Models\Log'
            ];
        });

        UsersController::extend(function($controller) {
            $controller->implement[] = 'Backend.Behaviors.RelationController';
            $controller->relationConfig =  __DIR__ . '/config/wallet_logs_relation.yaml';
        });

        UsersController::extendFormFields(function($form, $model, $context) {
            if (! $model instanceof \Rainlab\User\Models\User) return;
            $configFile = __DIR__ . '/config/profile.yaml';
            $config = Yaml::parse(File::get($configFile));
            $form->addTabFields($config);
        });
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'wallet' => [
                'label'       => 'Wallet',
                'url'         => Backend::url('octobro/wallet/logs'),
                'icon'        => 'icon-leaf',
                'permissions' => ['octobro.wallet.*'],
                'order'       => 500,
            ],
        ];
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Octobro\Wallet\Components\Wallet' => 'wallet',
        ];
    }

}
