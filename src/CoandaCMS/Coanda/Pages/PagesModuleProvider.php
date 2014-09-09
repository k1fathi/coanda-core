<?php namespace CoandaCMS\Coanda\Pages;

use Route, App, Config, Coanda, View, Cache;

use CoandaCMS\Coanda\Pages\Exceptions\PageNotFound;
use CoandaCMS\Coanda\Pages\Exceptions\PageTypeNotFound;
use CoandaCMS\Coanda\Pages\Exceptions\PageAttributeTypeNotFound;
use CoandaCMS\Coanda\Exceptions\PermissionDenied;

/**
 * Class PagesModuleProvider
 * @package CoandaCMS\Coanda\Pages
 */
class PagesModuleProvider implements \CoandaCMS\Coanda\CoandaModuleProvider {

    /**
     * @var string
     */
    public $name = 'pages';

    /**
     * @var array
     */
    private $page_types = [];

    /**
     * @var array
     */
    private $home_page_types = [];

    /**
     * @var array
     */
    private $publish_handlers = [];

    /**
     * @var
     */
    private $meta;

    /**
     * @param CoandaCMS\Coanda\Coanda $coanda
     */
    public function boot(\CoandaCMS\Coanda\Coanda $coanda)
	{
		$this->loadRouter($coanda);
		$this->loadPageTypes($coanda);
		$this->loadPublishHandlers($coanda);
		$this->loadPermissions($coanda);
	}

    /**
     * @param $coanda
     */
    private function loadRouter($coanda)
	{
		// Add the router to handle slug views
		$coanda->addRouter('pagelocation', function ($url) use ($coanda) {

			$cache_key = $this->generateCacheKey($url->type_id);

			if (Config::get('coanda::coanda.page_cache_enabled'))
			{
				if (Cache::has($cache_key))
				{
					return Cache::get($cache_key);
				}				
			}

			try
			{
				$location = $this->getPageRepository()->locationById($url->type_id);	

				return $this->renderPage($location->page, $location);
			}
			catch(PageNotFound $exception)
			{
				App::abort('404');
			}

		});
	}

    /**
     * @param $coanda
     */
    private function loadPageTypes($coanda)
	{
		// load the page types
		$page_types = Config::get('coanda::coanda.page_types');

		foreach ($page_types as $page_type)
		{
			if (class_exists($page_type))
			{
				$type = new $page_type($this);

				$this->page_types[$type->identifier()] = $type;
			}
		}

		// load the home page types
		$home_page_types = Config::get('coanda::coanda.home_page_types');

		foreach ($home_page_types as $home_page_type)
		{
			if (class_exists($home_page_type))
			{
				$type = new $home_page_type($this);

				$this->home_page_types[$type->identifier()] = $type;
			}
		}
	}

    /**
     * @param $coanda
     */
    private function loadPublishHandlers($coanda)
	{
		// Load the publish handlers
		$core_publish_handlers = [
			'CoandaCMS\Coanda\Pages\PublishHandlers\Immediate' // Make sure this one is always added (TODO: Consider removing this as 'core')
		];

		$enabled_publish_handlers = Config::get('coanda::coanda.publish_handlers');

		$publish_handlers = array_merge($core_publish_handlers, $enabled_publish_handlers);

		foreach ($publish_handlers as $publish_handler)
		{
			if (class_exists($publish_handler))
			{
				$handler = new $publish_handler;

				$this->publish_handlers[$handler->identifier] = $handler;
			}
		}
	}

    /**
     * @param $coanda
     */
    private function loadPermissions($coanda)
	{
		$publish_handler_options = [];

		foreach ($this->publish_handlers as $publish_handler)
		{
			$publish_handler_options[$publish_handler->identifier] = $publish_handler->name;
		}

		$page_type_options = [];

		foreach ($this->page_types as $page_type)
		{
			$page_type_options[$page_type->identifier()] = $page_type->name();
		}

		// Add the permissions
		$permissions = [
			'create' => [
				'name' => 'Create',
			],
			'edit' => [
				'name' => 'Edit',
			],
			'remove' => [
				'name' => 'Remove',
			],
			'publish_options' => [
				'name' => 'Publish options',
				'options' => $publish_handler_options
			],
			'page_types' => [
				'name' => 'Available page types',
				'options' => $page_type_options
			],
			'home_page' => [
				'name' => 'Home Page',
			],
			'locations' => [
				'name' => 'Locations',
				'location_paths' => true
			],
		];

		$coanda->addModulePermissions('pages', 'Pages', $permissions);		
	}

    /**
     * @param bool $page
     * @return array
     */
    public function availablePageTypes($page = false)
	{
		$page_types = $this->page_types;

		if ($page !== false)
		{
			$allowed_page_types = $page->pageType()->allowedSubPageTypes();

			if (count($allowed_page_types) > 0)
			{
				$page_types = [];

				foreach ($allowed_page_types as $allowed_page_type)
				{
					if (isset($this->page_types[$allowed_page_type]))
					{
						$page_types[$allowed_page_type] = $this->page_types[$allowed_page_type];
					}
				}
			}
		}

		$user_permissions = \Coanda::currentUserPermissions();

		if (isset($user_permissions['everything']) && in_array('*', $user_permissions['everything']))
		{
			return $page_types;
		}

		if (isset($user_permissions['pages']))
		{
			if (in_array('*', $user_permissions['pages']))
			{
				return $this->page_types;
			}

			if (in_array('create', $user_permissions['pages']))
			{
				if (isset($user_permissions['pages']['page_types']))
				{
					$new_page_types = [];

					foreach ($user_permissions['pages']['page_types'] as $permissioned_page_type)
					{
						if (isset($page_types[$permissioned_page_type]))
						{
							$new_page_types[$permissioned_page_type] = $page_types[$permissioned_page_type];
						}
					}

					return $new_page_types;
				}
				else
				{
					return $page_types;
				}
			}
		}

		return [];
	}

    /**
     * @return mixed
     */
    public function getPageRepository()
	{
		return App::make('CoandaCMS\Coanda\Pages\Repositories\PageRepositoryInterface');
	}

    /**
     * @param bool $page
     * @return array
     */
    public function availableHomePageTypes($page = false)
	{
		return $this->home_page_types;
	}

    /**
     * @param $type
     * @throws Exceptions\PageTypeNotFound
     * @return mixed
     */
    public function getPageType($type)
	{
		if (array_key_exists($type, $this->page_types))
		{
			return $this->page_types[$type];
		}

		throw new PageTypeNotFound;
	}

    /**
     * @param $type
     * @throws Exceptions\PageTypeNotFound
     * @return mixed
     */
    public function getHomePageType($type)
	{
		if (array_key_exists($type, $this->home_page_types))
		{
			return $this->home_page_types[$type];
		}

		throw new PageTypeNotFound;
	}

    /**
     * @return array
     */
    public function publishHandlers()
	{
		return $this->publish_handlers;
	}

    /**
     * @param $identifier
     * @return mixed
     */
    public function getPublishHandler($identifier)
	{
		return $this->publish_handlers[$identifier];
	}

    /**
     *
     */
    public function adminRoutes()
	{
		// Load the pages controller
		Route::controller('pages', 'CoandaCMS\Coanda\Controllers\Admin\PagesAdminController');
	}

    /**
     *
     */
    public function userRoutes()
	{
		// Front end routes for Pages (preview etc)
		Route::controller('pages', 'CoandaCMS\Coanda\Controllers\PagesController');
	}

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return mixed
     */
    public function bindings(\Illuminate\Foundation\Application $app)
	{
		$app->bind('CoandaCMS\Coanda\Pages\Repositories\PageRepositoryInterface', 'CoandaCMS\Coanda\Pages\Repositories\Eloquent\EloquentPageRepository');
	}

    /**
     * @param $permission
     * @param $parameters
     * @param array $user_permissions
     * @return mixed|void
     * @throws \CoandaCMS\Coanda\Exceptions\PermissionDenied
     */
    public function checkAccess($permission, $parameters, $user_permissions = [])
	{
		// Do we need to check the path permissions?
		if (isset($user_permissions['allowed_paths']) && count($user_permissions['allowed_paths']) > 0)
		{
			// Lets assume it passes
			$pass_path_check = true;

			if (isset($parameters['page_location_id']))
			{
				$location = Coanda::pages()->getLocation($parameters['page_location_id']);

				if ($location && isset($user_permissions['allowed_paths']) && count($user_permissions['allowed_paths']) > 0)
				{
					$pass_path_check = false;

					$location_path = $location->path . ($location->path == '' ? '/' : '') . $location->id . '/';

					foreach ($user_permissions['allowed_paths'] as $allowed_path)
					{
						if ($allowed_path == '')
						{
							continue;
						}

						if (preg_match('/^' . preg_replace('/\//', '\/', preg_quote($allowed_path)) . '/', $location_path))
						{
							$pass_path_check = true;
						}

						if ($permission == 'view')
						{
							if (preg_match('/^' . preg_replace('/\//', '\/', preg_quote($location_path)) . '/', $allowed_path))
							{
								$pass_path_check = true;
							}
						}
					}
				}
			}

			if (!$pass_path_check)
			{
				throw new PermissionDenied('Your are not allowed access to this location');
			}
		}

		if (in_array('*', $user_permissions))
		{
			return;
		}

		// If we anything in pages, we allow view
		if ($permission == 'view')
		{
			return;
		}

		// If we have create, but not edit, then add edit
		if (in_array('create', $user_permissions) && !in_array('edit', $user_permissions))
		{
			$user_permissions[] = 'edit';
		}

		// If we don't have this permission in the array, the throw right away
		if (!in_array($permission, $user_permissions))
		{
			throw new PermissionDenied('Access denied by pages module: ' . $permission);
		}

		// Page type check
		if ($permission == 'create' || $permission == 'edit' || $permission == 'remove')
		{
			if (isset($user_permissions['page_types']) && count($user_permissions['page_types']) > 0)
			{
				if (isset($parameters['page_type']) && !in_array($parameters['page_type'], $user_permissions['page_types']))
				{
					throw new PermissionDenied('Access denied by pages module for page type: ' . $parameters['page_type']);
				}
			}
		}

		return;
	}

    /**
     * @param $coanda
     * @return mixed|void
     */
    public function buildAdminMenu($coanda)
	{
		if ($coanda->canViewModule('pages'))
		{
			$coanda->addMenuItem('pages', 'Pages');	
		}
	}

    /**
     * @param $version
     * @internal param $page
     * @return mixed
     */
    private function getLayout($version)
	{
		if ($version->layout_identifier)
		{
			$layout = Coanda::layout()->layoutByIdentifier($version->layout_identifier);

			if ($layout)
			{
				return $layout;
			}
		}

		$page_type_layout = $version->page->pageType()->defaultLayout();

		if ($page_type_layout)
		{
			$layout = Coanda::layout()->layoutByIdentifier($page_type_layout);

			if ($layout)
			{
				return $layout;
			}
		}

		return Coanda::module('layout')->defaultLayout();
	}

    /**
     * @return mixed
     * @throws \Exception
     */
    public function renderHome()
	{
		$home_page = $this->getPageRepository()->getHomePage();

		if ($home_page)
		{
			$content = $this->renderPage($home_page);

			return $content;
		}

		throw new \Exception('Home page not created yet!');
	}

    /**
     * @param $page
     * @param $pagelocation
     * @return mixed
     */
    private function renderAttributes($page, $pagelocation)
    {
    	return $page->renderAttributes($pagelocation);
	}

    /**
     * @param $page
     * @param bool $pagelocation
     * @return mixed
     */
    private function renderPage($page, $pagelocation = false)
	{
		if ($page->is_trashed || !$page->is_visible || $page->is_hidden)
		{
			App::abort('404');
		}

		$data = $this->buildPageData($page, $pagelocation);

		// Does the page type want to do anything before we carry on with the rendering?
		// e.g. Redirect, set some additional data variables
		$data = $page->pageType()->preRender($data);

		// Lets check if we got a redirect request back...
		if (is_object($data) && get_class($data) == 'Illuminate\Http\RedirectResponse')
		{
			return $data;
		}

		// The page type works out the template to be used. The default is pretty simple, but more complex things could be done if required.
		$template = $page->pageType()->template($page->currentVersion(), $data);

		// Make the view and pass all the render data to it...
		// $rendered_page = View::make($template, $data)->render();
		$rendered_page = View::make($template, $data);

		return $this->mergeWithLayout($page, $pagelocation, $rendered_page);
	}

    /**
     * @param $page
     * @param $pagelocation
     * @param $rendered_content
     * @return mixed
     */
    private function mergeWithLayout($page, $pagelocation, $rendered_content)
	{
		// Get the layout template...
		$layout = $this->getLayout($page->currentVersion());

		// Give the layout the rendered page and the data, and it can work some magic to give us back a complete page...
		$layout_data = [
			'layout' => $layout,
			'content' => $rendered_content,
			'meta' => $this->buildMeta($page),
			'breadcrumb' => ($pagelocation ? $pagelocation->breadcrumb() : []),
			'module' => 'pages',
			'module_identifier' => $page->id
		];

		$content = $layout->render($layout_data);

		if (Config::get('coanda::coanda.page_cache_enabled'))
		{
			if ($page->pageType()->canStaticCache() && $pagelocation)
			{
				$cache_key = $this->generateCacheKey($pagelocation->id);

				$cache_time = Config::get('coanda::coanda.page_cache_lifetime');

				Cache::put($cache_key, $content, $cache_time);
			}
		}

		return $content;
	}

    /**
     * @param $location_id
     * @return string
     */
    private function generateCacheKey($location_id)
	{
		$cache_key = 'location-' . $location_id;

		$all_input = \Input::all();

		// If we are viewing ?page=1 - then this is cached the same as without it...
		if (isset($all_input['page']) && $all_input['page'] == 1)
		{
			unset($all_input['page']);
		}

		$cache_key .= '-' . md5(var_export($all_input, true));

		return $cache_key;
	}

    /**
     * @param $page
     * @return array
     */
    private function buildMeta($page)
	{
		if (!$this->meta)
		{
			$meta_title = $page->currentVersion()->meta_page_title;

			$this->meta = [
				'title' => $meta_title !== '' ? $meta_title : $page->present()->name,
				'description' => $page->currentVersion()->meta_description
			];
		}

		return $this->meta;
	}

    /**
     * @param $page
     * @param $pagelocation
     * @return array
     */
    private function buildPageData($page, $pagelocation)
	{
		return [
			'page_id' => $page->id,
			'version' => $page->current_version,
			'location_id' => ($pagelocation ? $pagelocation->id : false),
			'parent' => ($pagelocation ? $pagelocation->parent : false),
			'page' => $page,
			'attributes' => $this->renderAttributes($page, $pagelocation),
			'meta' => $this->buildMeta($page),
			'slug' => ($pagelocation ? $pagelocation->slug : ''),
		];
	}

    /**
     * @param $version
     * @return mixed
     */
    public function renderVersion($version)
	{
		$page = $version->page;
		$pagelocation = false;

		$meta_title = $version->meta_page_title;

		$meta = [
			'title' => $meta_title !== '' ? $meta_title : $version->present()->name,
			'description' => $version->meta_description
		];

		$attributes = new \stdClass;

		foreach ($version->attributes as $attribute)
		{
			$attributes->{$attribute->identifier} = $attribute->render($page, $pagelocation);
		}

		$first_location = $version->slugs()->first();

		$location = $first_location->location();

		if (!$location)
		{
			// Create a dummy location to simulate viewing a location
			$location = $first_location->tempLocation();	
		}

		$location_id = $location->id;

		$breadcrumb = $location->breadcrumb();

		// We need to take the last item off and replace it with the version name...
		array_pop($breadcrumb);

		$breadcrumb[] = [
			'url' => false,
			'identifier' => 'pages:location-' . $location->id,
			'layout_identifier' => 'pages:' . $page->id,
			'name' => $version->present()->name
		];

		$data = [
			'page' => $version->page,
			'location_id' => $location_id,
			'meta' => $meta,
			'attributes' => $attributes
		];

		// Make the view and pass all the render data to it...
		$rendered_version = View::make($page->pageType()->template($version, $data), $data);

		// Get the layout template...
		$layout = $this->getLayout($version);

		// Give the layout the rendered page and the data, and it can work some magic to give us back a complete page...
		$layout_data = [
			'layout' => $layout,
			'content' => $rendered_version,
			'meta' => $meta,
			'page_data' => $data,
			'breadcrumb' => $breadcrumb,
			'module' => 'pages',
			'module_identifier' => $page->id . ':' . $version->version
		];

		$content = View::make($layout->template(), $layout_data)->render();

		return $content;
	}

    /**
     * @param $page_id
     * @return bool
     */
    public function getPage($page_id)
	{
		try
		{
			return $this->getPageRepository()->findById($page_id);
		}
		catch (PageNotFound $exception)
		{
			return false;
		}
	}

    /**
     * @param $location_id
     * @return bool
     */
    public function getLocation($location_id)
	{
		try
		{
			return $this->getPageRepository()->locationById($location_id);
		}
		catch (PageNotFound $exception)
		{
			return false;
		}
	}

    /**
     * @param $slug
     * @return bool
     */
    public function bySlug($slug)
	{
		try
		{
			return $this->getPageRepository()->locationBySlug($slug);
		}
		catch (PageNotFound $exception)
		{
			return false;
		}
	}

    /**
     * @param $remote_id
     * @return bool
     */
    public function getLocationByRemoteId($remote_id)
	{
		try
		{
			return $this->getPageRepository()->getLocationByRemoteId($remote_id);
		}
		catch (PageNotFound $exception)
		{
			return false;
		}
	}

    /**
     * @return mixed
     */
    private function getQueryBuilder()
	{
		return App::make('CoandaCMS\Coanda\Pages\PageQuery');
	}

    /**
     * @return mixed
     */
    public function query()
	{
		return $this->getQueryBuilder();
	}

    /**
     * @param $query
     * @return mixed
     */
    public function adminSearch($query)
	{
		return $this->getPageRepository()->adminSearch($query);
	}

    /**
     * @param $path
     * @return bool
     */
    public function locationByPath($path)
	{
		$path_parts = explode('/', trim($path, '/'));

		if (count($path_parts) > 0)
		{
			$location_id = (int) array_pop($path_parts);

			try
			{
				return $this->getPageRepository()->locationById($location_id);
			}
			catch (PageNotFound $exception)
			{
				return false;
			}
		}

		return false;
	}
}