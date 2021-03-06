<?php namespace CoandaCMS\Coanda\Urls;

use Route, App, Config;

use CoandaCMS\Coanda\Exceptions\PermissionDenied;

/**
 * Class UrlModuleProvider
 * @package CoandaCMS\Coanda\Urls
 */
class UrlModuleProvider implements \CoandaCMS\Coanda\CoandaModuleProvider {

    /**
     * @var string
     */
    public $name = 'urls';

    /**
     * @param \CoandaCMS\Coanda\Coanda $coanda
     */
    public function boot(\CoandaCMS\Coanda\Coanda $coanda)
	{
		// Add the permissions
        $permissions = [
            'view' => [
                'name' => 'View',
                'options' => []
            ],
            'add' => [
                'name' => 'Add',
                'options' => []
            ],
            'remove' => [
                'name' => 'Remove',
                'options' => []
            ]
        ];

		$coanda->addModulePermissions('urls', 'Urls', $permissions);

        // Add the router to handle promo urls
        $coanda->addRouter('redirecturl', function ($url) use ($coanda) {

            $urlRepository = App::make('CoandaCMS\Coanda\Urls\Repositories\UrlRepositoryInterface');
            $redirect_url = $urlRepository->getRedirectUrl($url->type_id);

            if ($redirect_url)
            {
                $redirect_url->addHit();

                $status = ($redirect_url->redirect_type == 'perm') ? 301 : 302;

                return \Redirect::to(url($redirect_url->destination), $status);
            }

            App::abort('404');

        });
	}

    /**
     *
     */
    public function adminRoutes()
	{
		// Load the media controller
		Route::controller('urls', 'CoandaCMS\Coanda\Controllers\Admin\UrlAdminController');
	}

    /**
     *
     */
    public function userRoutes()
	{
	}

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return mixed
     */
    public function bindings(\Illuminate\Foundation\Application $app)
	{
	}

    /**
     * @param $permission
     * @param $parameters
     * @param $user_permissions
     * @return bool
     * @throws \CoandaCMS\Coanda\Exceptions\PermissionDenied
     */
    public function checkAccess($permission, $parameters, $user_permissions)
    {
        if (in_array('*', $user_permissions))
        {
            return true;
        }

        // If we anything in pages, we allow view
        if ($permission == 'view')
        {
            return;
        }

        // If we don't have this permission in the array, the throw right away
        if (!in_array($permission, $user_permissions))
        {
            throw new PermissionDenied('Access denied by media module: ' . $permission);
        }

        return;
    }

    /**
     * @param $coanda
     */
    public function buildAdminMenu($coanda)
    {
        if ($coanda->canViewModule('urls'))
        {
            $coanda->addMenuItem('urls', 'Urls');
        }
    }
}