<?php namespace Octobro\Wallet;

use Yaml;
use File;
use Backend;
use System\Classes\PluginBase;
use Illuminate\Foundation\AliasLoader;

/**
 * Wallet Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['Responsiv.Pay'];

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
            'icon'        => 'icon-google-wallet'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

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
                'icon'        => 'icon-google-wallet',
                'permissions' => ['octobro.wallet.*'],
                'order'       => 500,
                'sideMenu' => [
                    'logs' => [
                        'label'       => 'Logs',
                        'icon'        => 'icon-history',
                        'url'         => Backend::url('octobro/wallet/logs'),
                        'permissions' => ['octobro.wallet.*']
                    ],
                ]
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
