<?php namespace CoandaCMS\Coanda\Pages\Repositories\Eloquent;

use Coanda;

use CoandaCMS\Coanda\Pages\Exceptions\PageNotFound;
use CoandaCMS\Coanda\Pages\Exceptions\PageVersionNotFound;
use CoandaCMS\Coanda\Exceptions\AttributeValidationException;
use CoandaCMS\Coanda\Exceptions\ValidationException;

use CoandaCMS\Coanda\Pages\Exceptions\PublishHandlerException;
use CoandaCMS\Coanda\Pages\Exceptions\HomePageAlreadyExists;
use CoandaCMS\Coanda\Pages\Exceptions\SubPagesNotAllowed;

use CoandaCMS\Coanda\Urls\Exceptions\InvalidSlug;
use CoandaCMS\Coanda\Urls\Exceptions\UrlAlreadyExists;

use CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageLocation as PageLocationModel;
use CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\Page as PageModel;
use CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageVersion as PageVersionModel;
use CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageVersionSlug as PageVersionSlugModel;
use CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageVersionComment as PageVersionCommentModel;
use CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageAttribute as PageAttributeModel;

use CoandaCMS\Coanda\Pages\Repositories\PageRepositoryInterface;

use Carbon\Carbon;

class EloquentPageRepository implements PageRepositoryInterface {

    /**
     * @var Models\Page
     */
    private $page_model;
    /**
     * @var Models\PageVersion
     */
    private $page_version_model;
    /**
     * @var Models\PageAttribute
     */
    private $page_attribute_model;
    /**
     * @var Models\PageLocation
     */
    private $page_location_model;
    /**
     * @var Models\PageVersionSlug
     */
    private $page_version_slug_model;

    /**
     * @var Models\PageVersionComment
     */
    private $page_version_comment_model;

    /**
     * @var \CoandaCMS\Coanda\Urls\Repositories\UrlRepositoryInterface
     */
    private $urlRepository;
    /**
     * @var \CoandaCMS\Coanda\History\Repositories\HistoryRepositoryInterface
     */
    private $historyRepository;

    /**
     * @param PageLocationModel $page_location_model
     * @param PageModel $page_model
     * @param PageVersionModel $page_version_model
     * @param PageAttributeModel $page_attribute_model
     * @param PageVersionSlugModel $page_version_slug_model
     * @param CoandaCMS\Coanda\Urls\Repositories\UrlRepositoryInterface $urlRepository
     * @param CoandaCMS\Coanda\History\Repositories\HistoryRepositoryInterface $historyRepository
     */
    public function __construct(PageLocationModel $page_location_model, PageModel $page_model, PageVersionModel $page_version_model, PageAttributeModel $page_attribute_model, PageVersionSlugModel $page_version_slug_model, PageVersionCommentModel $page_version_comment_model, \CoandaCMS\Coanda\Urls\Repositories\UrlRepositoryInterface $urlRepository, \CoandaCMS\Coanda\History\Repositories\HistoryRepositoryInterface $historyRepository)
	{
		$this->page_location_model = $page_location_model;
		$this->page_version_model = $page_version_model;
		$this->page_attribute_model = $page_attribute_model;
		$this->page_version_slug_model = $page_version_slug_model;
		$this->page_version_comment_model = $page_version_comment_model;
		$this->page_model = $page_model;
		
		$this->urlRepository = $urlRepository;
		$this->historyRepository = $historyRepository;
	}

    /**
     * @param $id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     */
    public function find($id)
	{
		return $this->findById($id);
	}

    /**
     * @param $id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     */
    public function findById($id)
	{
		$page = $this->page_model->find($id);

		if (!$page)
		{
			throw new PageNotFound('Page #' . $id . ' not found');
		}
		
		return $page;
	}

    /**
     * @param $id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     */
    public function locationById($id)
	{
		$location = $this->page_location_model->find($id);

		if (!$location)
		{
			throw new PageNotFound('Page Location #' . $id . ' not found');
		}

		return $location;
	}

    /**
     * @param $id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     */
	public function getByRemoteId($remote_id)
	{
		$page = $this->page_model->whereRemoteId($remote_id)->first();

		if (!$page)
		{
			throw new PageNotFound('Page with remote id: ' . $remote_id . ' not found');
		}
		
		return $page;
	}

    /**
     * @param $limit
     * @param $offset
     * @return mixed
     */
    public function locations($limit, $offset)
	{
		return $this->page_location_model->take($limit)->skip($offset)->get();
	}

    /**
     * @param $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByIds($ids)
	{
		$pages = new \Illuminate\Database\Eloquent\Collection;

		if (!is_array($ids))
		{
			return $pages;
		}

		foreach ($ids as $id)
		{
			$page = $this->page_model->find($id);

			if ($page)
			{
				$pages->add($page);
			}
		}

		return $pages;
	}

    /**
     * @param $parent_location_id
     * @param int $per_page
     * @return mixed
     */
    private function subLocations($parent_location_id, $per_page = 10)
	{
		$order = 'manual';

		if ($parent_location_id != 0)
		{
			$parent = $this->locationById($parent_location_id);

			if ($parent)
			{
				$order = $parent->sub_location_order;
			}
		}

		$query = $this->page_location_model->where('parent_page_id', $parent_location_id)->whereHas('page', function ($query) { $query->where('is_trashed', '=', '0'); });

		if ($order == 'manual')
		{
			$query->orderBy('order', 'asc');
		}

		if ($order == 'alpha:asc')
		{
			$query->orderByPageName('asc');
		}

		if ($order == 'alpha:desc')
		{
			$query->orderByPageName('desc');
		}

		if ($order == 'created:asc')
		{
			$query->orderByPageCreated('asc');
		}

		if ($order == 'created:desc')
		{
			$query->orderByPageCreated('desc');
		}

		return $query->paginate($per_page);
	}

    /**
     * @param int $per_page
     * @return mixed
     */
    public function topLevel($per_page = 10)
	{
		return $this->subLocations(0, $per_page);
	}

    /**
     * @param $location_id
     * @param $per_page
     * @return mixed
     */
    public function subPages($location_id, $per_page)
	{
		return $this->subLocations($location_id, $per_page);
	}

    /**
     * @param $type
     * @param $is_home
     * @param $user_id
     * @param bool $parent_pagelocation_id
     * @return mixed
     */
    private function createNewPage($type, $is_home, $user_id, $parent_pagelocation_id = false)
	{
		// Create the page...
		$page_data = [
			'is_home' => $is_home,
			'type' => $type->identifier(),
			'created_by' => $user_id,
			'edited_by' => $user_id,
			'current_version' => 1
		];

		$page = $this->page_model->create($page_data);

		// Create the version...
		$version_data = [
			'page_id' => $page->id,
			'version' => 1,
			'status' => 'draft',
			'created_by' => $user_id,
			'edited_by' => $user_id,
		];

		$version = $this->page_version_model->create($version_data);

		// Create all the attributes...
		$index = 1;

		foreach ($type->attributes() as $type_attribute)
		{
			$page_attribute_type = Coanda::getAttributeType($type_attribute['type']);

			$attribute_data = [
				'page_version_id' => $version->id,
				'identifier' => $type_attribute['identifier'],
				'type' => $page_attribute_type->identifier(),
				'order' => $index
			];

			$attribute = $this->page_attribute_model->create($attribute_data);

			$index ++;
		}

		// If we are dealing with the home page, then we don't need to add a location
		if (!$is_home)
		{
			$location_data = [
				'page_id' => $page->id,
				'parent_page_id' => $parent_pagelocation_id ? $parent_pagelocation_id : 0
			];

			$location = $this->page_location_model->create($location_data);

			$version_slug_data = [
				'version_id' => $version->id,
				'page_location_id' => $parent_pagelocation_id ? $parent_pagelocation_id : 0,
				'slug' => ''
			];

			$version_slug = $this->page_version_slug_model->create($version_slug_data);
		}

		// Log the history
		$this->historyRepository->add('pages', $page->id, $user_id, 'initial_version');

		return $page;
	}

    /**
     * @param $type
     * @param $user_id
     * @param bool $parent_location_id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\SubPagesNotAllowed
     */
    public function create($type, $user_id, $parent_location_id = false)
	{
		if ($parent_location_id)
		{
			$parent_location = $this->locationById($parent_location_id);

			if ($parent_location->page->pageType()->allowsSubPages())
			{
				return $this->createNewPage($type, false, $user_id, $parent_location->id);
			}			

			throw new SubPagesNotAllowed('This page type does not allow sub pages');
		}
		else
		{
			return $this->createNewPage($type, false, $user_id, $parent_location_id);
		}
	}

	public function createAndPublish($type, $user_id, $parent_location_id, $page_data)
	{
		$page = $this->create($type, $user_id, $parent_location_id);
		$version = $page->currentVersion();

		// Add the slug data
		foreach ($version->slugs as $slug)
		{
			$page_data['slug_' . $slug->id] = $page_data['slug'];
		}

		$this->saveDraftVersion($version, $page_data);
		$this->publishVersion($version, $user_id, $this->urlRepository, $this->historyRepository);

		return $this->find($page->id);
	}

    /**
     * @param $type
     * @param $user_id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\HomePageAlreadyExists
     */
    public function createHome($type, $user_id)
	{
		// Check we don't already have a home page...
		$home = $this->getHomePage();

		if (!$home)
		{
			return $this->createNewPage($type, true, $user_id);	
		}

		throw new HomePageAlreadyExists('You already have a home page defined');
	}

	public function createAndPublishHome($type, $user_id, $page_data)
	{
		$page = $this->createHome($type, $user_id);
		$version = $page->currentVersion();

		$this->saveDraftVersion($version, $page_data);
		$this->publishVersion($version, $user_id, $this->urlRepository, $this->historyRepository);

		return $page;
	}

    /**
     * @param $version_id
     * @param $page_location_id
     */
    public function addNewVersionSlug($version_id, $page_location_id)
	{
		$version = $this->getVersionById($version_id);

		$existing = $version->slugs()->wherePageLocationId($page_location_id)->first();

		if (!$existing)
		{
			$version_slug_data = [
				'version_id' => $version->id,
				'page_location_id' => $page_location_id,
				'slug' => ''
			];

			$version_slug = $this->page_version_slug_model->create($version_slug_data);
		}
	}

    /**
     * @param $version_id
     * @param $slug_id
     */
    public function removeVersionSlug($version_id, $slug_id)
	{
		$version = $this->getVersionById($version_id);

		$slug = $version->slugs()->whereId($slug_id)->first();

		if ($slug)
		{
			$slug->delete();
		}
	}

    /**
     * @param $page_id
     * @param $version
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageVersionNotFound
     */
    public function getDraftVersion($page_id, $version)
	{
		$page = $this->page_model->find($page_id);

		if ($page)
		{
			$version = $page->versions()->whereStatus('draft')->whereVersion($version)->first();

			if ($version)
			{
				// Let the version update/check its attributes against the definition (which might have changed)
				$version->checkAttributes();

				return $version;
			}

			throw new PageVersionNotFound;
		}

		throw new PageNotFound;
	}

    /**
     * @param $id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageVersionNotFound
     */
    public function getVersionById($id)
	{
		$version = $this->page_version_model->find($id);

		if (!$version)
		{
			throw new PageVersionNotFound;
		}

		return $version;
	}

    /**
     * @param $preview_key
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageVersionNotFound
     */
    public function getVersionByPreviewKey($preview_key)
	{
		$version = $this->page_version_model->wherePreviewKey($preview_key)->whereStatus('draft')->first();

		if (!$version)
		{
			throw new PageVersionNotFound;
		}

		return $version;
	}

    /**
     * @param $version
     * @param $data
     * @throws \CoandaCMS\Coanda\Exceptions\ValidationException
     */
    public function saveDraftVersion($version, $data)
	{
		$failed = [];

		foreach ($version->attributes as $attribute)
		{
			try
			{
				$attribute_data = isset($data['attribute_' . $attribute->identifier]) ? $data['attribute_' . $attribute->identifier] : null;

				$attribute->store($attribute_data, 'attribute_' . $attribute->identifier);
			}
			catch (AttributeValidationException $exception)
			{
				$failed['attribute_' . $attribute->identifier]['message'] = $exception->getMessage();
				$failed['attribute_' . $attribute->identifier]['validation_data'] = $exception->getValidationData();
			}
		}

		// If we are dealing with the home page, then the slug doesn't matter
		if (!$version->page->is_home)
		{
			if ($version->slugs()->count() > 0)
			{
				// Check each of the locations to see if the slug is OK
				foreach ($version->slugs as $slug)
				{
					try
					{
						$location_id = $slug->location ? $slug->location->id : 0;

						$this->urlRepository->canUse($slug->base_slug . $data['slug_' . $slug->id], 'pagelocation', $location_id);

						$slug->slug = $data['slug_' . $slug->id];
						$slug->save();
					}
					catch(InvalidSlug $exception)
					{
						$failed['slug_' . $slug->id] = 'The slug is not valid';
					}
					catch(UrlAlreadyExists $exception)
					{
						$failed['slug_' . $slug->id] = 'The slug is already in use';
					}
				}
			}
			else
			{
				$failed['slugs'] = 'Please choose at least one location';
			}
		}

		// Get the meta
		if ($version->page->show_meta)
		{
			$version->meta_page_title = isset($data['meta_page_title']) ? $data['meta_page_title'] : false;
			$version->meta_description = isset($data['meta_description']) ? $data['meta_description'] : false;
		}

		// Get the visible_from and to dates
		$format = isset($data['date_format']) ? $data['date_format'] : Config::get('coanda::coanda.datetime_format');

		if ($format)
		{
			$dates = [
					'from' => false,
					'to' => false
				];

			$date_error = false;

			foreach (array_keys($dates) as $date)
			{
				if (isset($data['visible_dates'][$date]) && $data['visible_dates'][$date] !== '')
				{
					try
					{
						$dates[$date] = Carbon::createFromFormat($format, $data['visible_dates'][$date], date_default_timezone_get());

						// if ($dates[$date]->isPast())
						// {
						// 	$failed[$date] = 'The specified date is in past';

						// 	$date_error = true;
						// }
					}
					catch(\InvalidArgumentException $exception)
					{
						$failed[$date] = 'The specified date is invalid';

						$date_error = true;
					}
				}
			}

			if (!$date_error && $dates['from'] && $dates['to'])
			{
				// Check that the from date is before the to date
				if (!$dates['from']->lt($dates['to']))
				{
					$failed['visible_dates_to'] = 'The date must be after the visible from date';
				}
			}

			if ($dates['from'])
			{
				$version->visible_from = $dates['from'];
			}

			if ($dates['to'])
			{
				$version->visible_to = $dates['to'];
			}

			// If we have a blank date, null it
			if (isset($data['visible_dates']['from']) && $data['visible_dates']['from'] == '')
			{
				$version->visible_from = null;
			}

			// If we have a blank date, null it
			if (isset($data['visible_dates']['to']) && $data['visible_dates']['to'] == '')
			{
				$version->visible_to = null;
			}
		}

		if (isset($data['layout_identifier']))
		{
			if ($data['layout_identifier'] !== '')
			{
				$layout = Coanda::module('layout')->layoutByIdentifier($data['layout_identifier']);

				if ($layout)
				{
					$version->layout_identifier = $data['layout_identifier'];
				}
			}
			else
			{
				$version->layout_identifier = '';
			}
		}

		$version->save();

		if (count($failed) > 0)
		{
			throw new ValidationException($failed, $version->id);
		}
	}

    /**
     * @param $version
     */
    public function discardDraftVersion($version, $user_id)
	{
		$page = $version->page;

		// Log the history
		$this->historyRepository->add('pages', $page->id, $user_id, 'discard_version', ['version' => $version->version]);

		$version->delete();

		// If now have no versions, then remove the page too
		if ($page->versions->count() == 0)
		{
			// Log the history
			$this->historyRepository->add('pages', $page->id, $user_id, 'page_deleted');

			$page->delete();
		}
	}

    /**
     * @param $page_id
     * @param $user_id
     * @return mixed
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     */
    public function draftsForUser($page_id, $user_id)
	{
		$page = $this->page_model->find($page_id);

		if ($page)
		{
			return $page->versions()->whereStatus('draft')->whereCreatedBy($user_id)->get();
		}

		throw new PageNotFound;
	}

    /**
     * @param $version
     * @param $user_id
     * @param $urlRepository
     * @param $historyRepository
     */
    public function publishVersion($version, $user_id, $urlRepository, $historyRepository)
	{
		$page = $version->page;

		if ((int)$version->version !== 1)
		{
			// set the current published version to be archived
			$page->currentVersion()->status = 'archived';
			$page->currentVersion()->save();			
		}

		// set this version to be published
		$version->status = 'published';
		$version->save();
		
		// update the page name attribute (via the type)
		$page->name = $page->pageType()->generateName($version);
		$page->current_version = $version->version;
		$page->save();

		// If we are dealing with the home page, not need to worry about the URL...
		if (!$version->page->is_home)
		{
			$version_locations = $version->slugs()->lists('page_location_id');

			// Work out if we need to remove any locations...
			foreach ($page->locations as $location)
			{
				if (!in_array($location->parent_page_id, $version_locations))
				{
					$urlRepository->delete('pagelocation', $location->id);

					$this->unRegisterLocationWithSearchProvider($location);

					$location->delete();
				}
			}

			foreach ($version->slugs as $slug)
			{
				// Is the location already registered for this page?
				$location = $page->locations()->whereParentPageId($slug->page_location_id)->first();

				if (!$location)
				{
					$location_data = [
						'page_id' => $page->id,
						'parent_page_id' => $slug->page_location_id ? $slug->page_location_id : 0
					];

					$location = $this->page_location_model->create($location_data);
				}

				$url = $urlRepository->register($slug->full_slug, 'pagelocation', $location->id);
			}
		}

		// Log the history
		$historyRepository->add('pages', $page->id, $user_id, 'publish_version', ['version' => (int)$version->version]);

		// Tell the search engine about it!
		foreach ($page->locations as $location)
		{
			$this->registerLocationWithSearchProvider($location);
		}
	}


    /**
     * @param $location
     */
    public function registerLocationWithSearchProvider($location)
	{
		$page = $location->page;
		$version = $page->currentVersion();

		$search_data = [
			'page_type' => $page->type,
			'name' => $page->present()->name,
			'visible_from' => $version->visible_from,
			'visible_to' => $version->visible_to,
		];

		foreach ($page->attributes as $attribute)
		{
			$search_data[$attribute->identifier] = $attribute->render($page, $location, true);
		}

		Coanda::search()->register('pages', $location->id, $location->slug, $search_data);
	}

    /**
     * @param $location
     */
    public function unRegisterLocationWithSearchProvider($location)
	{
		$page = $location->page;

		Coanda::search()->unRegister('pages', $location->id);
	}

    /**
     * @param $version
     * @param $publish_handler
     * @param $data
     * @return mixed
     */
    public function executePublishHandler($version, $publish_handler, $data)
	{
		$publish_handler = Coanda::module('pages')->getPublishHandler($publish_handler);

		if ($publish_handler)
		{
			$version->publish_handler = $publish_handler->identifier;

			// Validate the publish handler - this can throw an exception if needs be!
			$publish_handler->validate($version, $data);

			// Return the result of the publish handler - this should be a redirect URL of null/false as required.
			return $publish_handler->execute($version, $data, $this, $this->urlRepository, $this->historyRepository);
		}
	}

    /**
     * @param $page_id
     * @param $user_id
     * @return mixed
     */
    public function createNewVersion($page_id, $user_id, $base_version_number = false)
	{
		$page = $this->page_model->find($page_id);
		$type = $page->pageType();

		if ($base_version_number)
		{
			$current_version = $page->getVersion($base_version_number);
		}
		else
		{
			$current_version = $page->currentVersion();	
		}

		if (!$current_version)
		{
			throw new PageVersionNotFound('Version #' . $base_version_number . ' could not be found');
		}

		$latest_version = $page->versions()->orderBy('version', 'desc')->first();

		$new_version_number = $latest_version->version + 1;

		// Create the new version...
		$version_data = [
			'page_id' => $page->id,
			'version' => $new_version_number,
			'status' => 'draft',
			'created_by' => $user_id,
			'edited_by' => $user_id,
			'meta_page_title' => $current_version->meta_page_title,
			'meta_description' => $current_version->meta_description,
			'visible_from' => $current_version->visible_from,
			'visible_to' => $current_version->visible_to,
			'layout_identifier' => $current_version->layout_identifier
		];

		$version = $this->page_version_model->create($version_data);

		// Now lets replicate the slugs
		foreach ($current_version->slugs as $slug)
		{
			$version_slug_data = [
				'version_id' => $version->id,
				'page_location_id' => $slug->page_location_id,
				'slug' => $slug->slug
			];

			$version_slug = $this->page_version_slug_model->create($version_slug_data);
		}

		// Add all the attributes..
		$index = 1;

		foreach ($type->attributes() as $type_attribute)
		{
			$page_attribute_type = Coanda::getAttributeType($type_attribute['type']);

			// Copy the attribute data from the current version
			$existing_attribute = $current_version->getAttributeByIdentifier($type_attribute['identifier']);

			$attribute_data = [
				'page_version_id' => $version->id,
				'identifier' => $type_attribute['identifier'],
				'type' => $page_attribute_type->identifier(),
				'order' => $index,
				'attribute_data' => $existing_attribute ? $existing_attribute->attribute_data : ''
			];

			$attribute = $this->page_attribute_model->create($attribute_data);

			$from_attribute_data = [];

			if ($existing_attribute)
			{
				$from_attribute_data = [
					'attribute_id' => $existing_attribute->id,
					'page_id' => $existing_attribute->page()->id,
					'version_number' => $existing_attribute->version->version
				];
			}

			$to_attribute_data = [
				'attribute_id' => $attribute->id,
				'page_id' => $attribute->page()->id,
				'version_number' => $attribute->version->version
			];

			$page_attribute_type->initialise($from_attribute_data, $to_attribute_data);

			$index ++;
		}

		// Log the history
		$this->historyRepository->add('pages', $page_id, $user_id, 'new_version', ['version' => $new_version_number]);

		return $new_version_number;
	}

    /**
     * @param $page_id
     * @param int $limit
     * @return mixed
     */
    public function recentHistory($page_id, $limit = 10)
	{
		return $this->historyRepository->get('pages', $page_id, $limit);
	}

    /**
     * @param $page_id
     * @return mixed
     */
    public function history($page_id)
	{
		return $this->historyRepository->getPaginated('pages', $page_id);
	}

    /**
     * @param $page_id
     * @return mixed
     */
    public function contributors($page_id)
	{
		return $this->historyRepository->users('pages', $page_id);
	}

    /**
     * @param $page_id
     * @param bool $permanent
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     */
    public function deletePage($page_id, $permanent = false)
	{
		$page = $this->page_model->find($page_id);

		if (!$page)
		{
			throw new PageNotFound;
		}

		if ($permanent)
		{
			$this->deleteSubPages($page, true);			

			foreach ($page->locations as $location)
			{
				$this->deleteLocation($location);
			}

			// Finally, we can remove this page
			$page->delete();

			$this->historyRepository->add('pages', $page->id, Coanda::currentUser()->id, 'deleted');
		}
		else
		{
			if (!$page->is_trashed)
			{
				$this->deleteSubPages($page, false);

				$page->is_trashed = true;
				$page->save();

				$this->historyRepository->add('pages', $page->id, Coanda::currentUser()->id, 'trashed');
			}
		}
	}

    /**
     * @param $location
     */
    public function deleteLocation($location)
	{
		$this->urlRepository->delete('pagelocation', $location->id);
		$this->unRegisterLocationWithSearchProvider($location);

		$location->delete();
	}

    /**
     * @param $page_ids
     * @param bool $permanent
     */
    public function deletePages($page_ids, $permanent = false)
	{
		if (count($page_ids) > 0)
		{
			foreach ($page_ids as $page_id)
			{
				try
				{
					$this->deletePage($page_id, $permanent);
				}
				catch (PageNotFound $exception)
				{
				}
			}
		}
	}

    /**
     * @param $page
     * @param bool $permanent
     */
    private function deleteSubPages($page, $permanent = false)
	{
		// Loop through the locations and set the sub pages to be deleted
		if ($page->locations->count() > 0)
		{
			foreach ($page->locations as $location)
			{
				$base_path = $location->path == '' ? '/' : $location->path;

				$sub_page_ids = $this->page_location_model->where('path', 'like', $base_path . $location->id . '/%')->lists('page_id');

				if (count($sub_page_ids) > 0)
				{
					if ($permanent)
					{
						foreach ($sub_page_ids as $sub_page_id)
						{
							$page = $this->page_model->find($sub_page_id);

							if ($page)
							{
								if ($page->locations->count() > 0)
								{
									foreach ($page->locations as $location)
									{
										$this->deleteLocation($location);
									}									
								}

								$page->delete();
							}
						}
					}
					else
					{
						$this->page_model->whereIn('id', $sub_page_ids)->update(['is_trashed' => true]);	
					}
				}
			}
		}
	}

    /**
     * @return mixed
     */
    public function trashed()
	{
		return $this->page_model->whereIsTrashed(true)->get();
	}

    /**
     * @param $page_id
     * @param array $restore_sub_pages
     * @throws \CoandaCMS\Coanda\Pages\Exceptions\PageNotFound
     */
    public function restore($page_id, $restore_sub_pages = [])
	{
		$page = $this->page_model->find($page_id);

		if (!$page)
		{
			throw new PageNotFound;
		}

		$page->is_trashed = false;
		$page->save();

		if ($page->locations->count() > 0)
		{
			foreach ($page->locations as $location)
			{
				// Is the parent page trashed? If so, we need to restore that - which will be recursive if its parent is trashed...
				if ($location->parent)
				{
					if ($location->parent->page->is_trashed)
					{
						$this->restore($location->parent->page->id);
					}
				}

				// Are we restoring the sub pages?
				if (in_array($location->id, $restore_sub_pages))
				{
					$base_path = $location->path == '' ? '/' : $location->path;

					$sub_page_ids = $this->page_location_model->where('path', 'like', $base_path . $location->id . '/%')->lists('page_id');

					if (count($sub_page_ids) > 0)
					{
						$this->page_model->whereIn('id', $sub_page_ids)->update(['is_trashed' => false]);
					}			
				}
			}
		}

		$this->historyRepository->add('pages', $page->id, Coanda::currentUser()->id, 'restored');
	}

    /**
     * @param $new_orders
     */
    public function updateOrdering($new_orders)
	{
		foreach ($new_orders as $location_id => $new_order)
		{
			$this->page_location_model->whereId($location_id)->update(['order' => $new_order]);
			$this->historyRepository->add('pages', $location_id, Coanda::currentUser()->id, 'order_changed', ['new_order' => $new_order]);
		}
	}

    /**
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function getPendingVersions($offset, $limit)
	{
		return $this->page_version_model->whereStatus('pending')->take($limit)->offset($offset)->get();
	}

    /**
     * @return mixed
     */
    public function getHomePage()
	{
		return $this->page_model->whereIsHome(true)->first();
	}

    /**
     * @param $location_id
     * @param $new_sub_page_order
     */
    public function updateLocationSubPageOrder($location_id, $new_sub_page_order)
	{
		$pagelocation = $this->locationById($location_id);

		$pagelocation->sub_location_order = $new_sub_page_order;
		$pagelocation->save();
	}

    /**
     * @param $version
     * @param $action_data
     * @param $data
     */
    public function handleAttributeAction($version, $action_data, $data)
	{
		foreach ($version->attributes as $attribute)
		{
			if (array_key_exists('attribute_' . $attribute->id, $action_data))
			{
				$attribute_data = isset($data['attribute_' . $attribute->id]) ? $data['attribute_' . $attribute->id] : false;
				$attribute->handleAction($action_data['attribute_' . $attribute->id], $attribute_data);
			}
		}
	}

    /**
     * @param $version
     * @param $data
     * @return mixed
     * @throws \CoandaCMS\Coanda\Exceptions\ValidationException
     */
    public function addVersionComment($version, $data)
	{
		$invalid_fields = [];

		if (!$data['name'] || $data['name'] == '')
		{
			$invalid_fields['name'] = 'Please enter your name';
		}

		if (!$data['comment'] || $data['comment'] == '')
		{
			$invalid_fields['comment'] = 'Please enter a comment';
		}

		if (count($invalid_fields) > 0)
		{
			throw new ValidationException($invalid_fields);
		}

		$comment_data = [
			'version_id' => $version->id,
			'name' => $data['name'],
			'comment' => $data['comment']
		];

		$comment = $this->page_version_comment_model->create($comment_data);

		// Send email to the version 'owner' - e.g. New coment on your draft,

		return $comment;
	}
}