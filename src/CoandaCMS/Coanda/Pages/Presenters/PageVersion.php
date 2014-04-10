<?php namespace CoandaCMS\Coanda\Pages\Presenters;

use Lang;

/**
 * Class PageVersion
 * @package CoandaCMS\Coanda\Pages\Presenters
 */
class PageVersion extends \CoandaCMS\Coanda\Core\Presenters\Presenter {

    /**
     * @return mixed
     */
    public function status()
	{
		return Lang::get('coanda::pages.status_' . $this->model->status);
	}

    /**
     * @return string
     */
    public function preview_url()
	{
		return 'pages/preview/' . $this->model->preview_key;
	}

    public function visible_from_date()
    {
        if (!$this->model->visible_from)
        {
            return '';
        }

        return $this->format_date('visible_from', 'd/m/Y');
    }

    public function visible_from_time()
    {
        if (!$this->model->visible_from)
        {
            return '';
        }

        return $this->format_date('visible_from', 'h:i');
    }

    public function visible_from()
    {
        if (!$this->model->visible_from)
        {
            return '';
        }

        return $this->format_date('visible_from');
    }

    public function visible_to()
    {
        if (!$this->model->visible_to)
        {
            return '';
        }

        return $this->format_date('visible_to');
    }
}