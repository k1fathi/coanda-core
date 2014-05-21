<?php namespace CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models;

use Eloquent, Coanda, App;
use Carbon\Carbon;

class PageVersionComment extends Eloquent {

    use \CoandaCMS\Coanda\Core\Presenters\PresentableTrait;

    protected $presenter = 'CoandaCMS\Coanda\Pages\Presenters\PageVersionComment';

    /**
     * @var string
     */
    protected $table = 'pageversioncomments';

    /**
     * @var array
     */
    protected $fillable = ['version_id', 'name', 'comment'];

    /**
     * @return mixed
     */
    public function version()
	{
		return $this->belongsTo('CoandaCMS\Coanda\Pages\Repositories\Eloquent\Models\PageVersion');
	}
}