<?php namespace CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models;

use Eloquent, Coanda, App;

class Page extends Eloquent {

	use \CoandaCMS\Coanda\Core\Presenters\PresentableTrait;

	protected $presenter = 'CoandaCMS\Coanda\Pages\Presenters\Page';

	private $pageType;
	private $slug;
	private $currentVersion;
	private $parents;
	private $children;
	private $subTreeCount;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'pages';

	public function delete()
	{
		foreach ($this->versions() as $version)
		{
			$version->delete();
		}

		parent::delete();
	}

	/**
	 * Get the versions for this page
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function versions()
	{
		return $this->hasMany('CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageVersion');
	}

	/**
	 * Returns the parent page for this page
	 * @return CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\Page [description]
	 */
	public function parent()
	{
		return $this->belongsTo('CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\Page', 'parent_page_id');
	}

	/**
	 * Returns all the children of this page
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function children()
	{
		if (!$this->children)
		{
			if ($this->is_trashed)
			{
				$this->children = $this->hasMany('CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\Page', 'parent_page_id')->orderBy('order', 'asc');
			}
			else
			{
				$this->children = $this->hasMany('CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\Page', 'parent_page_id')->orderBy('order', 'asc')->whereIsTrashed(0);	
			}
		}
		
		return $this->children;
	}

	public function subTreeCount()
	{
		if (!$this->subTreeCount)
		{
			$path = $this->path == '' ? '/' : $this->path;

			$this->subTreeCount = Page::where('path', 'like', $path . $this->id . '/%')->count();
		}

		return $this->subTreeCount;
	}

	public function pathArray()
	{
		return explode('/', $this->path);
	}

	public function depth()
	{
		return count($this->pathArray());
	}

	public function getDepthAttribute()
	{
		return $this->depth();
	}

	/**
	 * Loop through the path and build up the collection of parents
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function parents()
	{
		if (!$this->parents)
		{
			$this->parents = new \Illuminate\Database\Eloquent\Collection;

			foreach ($this->pathArray() as $parent_id)
			{
				$parent = $this->find($parent_id);

				if ($parent)
				{
					$this->parents->add($parent);					
				}
			}
		}

		return $this->parents;
	}

	/**
	 * Get the page type for this page
	 * @return CoandaCMS\Coanda\Pages\PageTypeInterface [description]
	 */
	public function pageType()
	{
		if (!$this->pageType)
		{
			$this->pageType = Coanda::getPageType($this->type);
		}

		return $this->pageType;
	}

	/**
	 * Gets the name of the type
	 * @return srting
	 */
	public function typeName()
	{
		return $this->pageType()->name;
	}

	/**
	 * Calls the typeName method
	 * @return string
	 */
	public function getTypeNameAttribute()
	{
		return $this->typeName();
	}

	/**
	 * Returns the current version object for this page
	 * @return CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageVersion
	 */
	public function currentVersion()
	{
		if (!$this->currentVersion)
		{
			$this->currentVersion = $this->versions()->whereVersion($this->current_version)->first();
		}

		return $this->currentVersion;
	}

	/**
	 * Get the status of the current version
	 * @return string
	 */
	public function getStatusAttribute()
	{
		return $this->currentVersion()->status;
	}

	/**
	 * Return all the attributes for the current version
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function getAttributesAttribute()
	{
		return $this->currentVersion()->attributes()->get();
	}

	/**
	 * Returns the slug for this page
	 * @return CoandaCMS\Coanda\Urls\Repositories\Eloquent\Models\Url
	 */
	public function getSlugAttribute()
	{
		if (!$this->slug)
		{
			$urlRepository = App::make('CoandaCMS\Coanda\Urls\Repositories\UrlRepositoryInterface');

			try
			{
				$this->slug = $urlRepository->findFor('page', $this->id)->slug;
			}
			catch(\CoandaCMS\Coanda\Urls\Exceptions\UrlNotFound $exception)
			{
				$this->slug = '';
			}
		}

		return $this->slug;
	}

	/**
	 * Check if the status of the current version is a draft
	 * @return boolean
	 */
	public function getIsDraftAttribute()
	{
		return $this->currentVersion()->status == 'draft';
	}

	public function getShowMetaAttribute()
	{
		$type = $this->pageType();

		return $type->showMeta();
	}
}