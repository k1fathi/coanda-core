<?php namespace CoandaCMS\Coanda\History\Repositories\Eloquent\Models;

use CoandaCMS\Coanda\Users\Exceptions\UserNotFound;
use CoandaCMS\Coanda\Users\Repositories\Eloquent\Models\User;
use Eloquent, Coanda, App;

/**
 * Class History
 * @package CoandaCMS\Coanda\History\Repositories\Eloquent\Models
 */
class History extends Eloquent {

	use \CoandaCMS\Coanda\Core\Presenters\PresentableTrait;

    /**
     * @var string
     */
    protected $presenter = 'CoandaCMS\Coanda\History\Presenters\History';

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'history';

    /**
     * @return mixed
     */
    public function user()
	{
		$userRepository = \App::make('CoandaCMS\Coanda\Users\Repositories\UserRepositoryInterface');

		try
		{
			$user = $userRepository->find($this->user_id);
		}
		catch (UserNotFound $exception)
		{
			$user = $userRepository->findArchivedUser($this->user_id);
		}

		if ($this->user_id == 0)
		{
			return $this->systemUser();
		}

		return $user;
	}

	/**
	 * @return User
     */
	private function systemUser()
	{
		$system_user = new User();
		$system_user->first_name = 'System';
		$system_user->last_name = 'User';
		$system_user->email = 'hello@somewhere.com';

		return $system_user;
	}

    /**
     * @return mixed
     */
    public function getUserAttribute()
	{
		return $this->user();
	}

    /**
     * @return mixed
     */
    public function actionData()
	{
		$array = json_decode($this->data, true);
		
		return !$array ? $this->data : $array;
	}

    /**
     * @return mixed
     */
    public function getActionDataAttribute()
	{
		return $this->actionData();
	}

	/**
	 * @return mixed
     */
	public function getModuleAttribute()
	{
		return $this->module();
	}

	/**
	 * @return mixed
     */
	public function module()
	{
		if (strpos($this->for, ':'))
		{
			return explode(':', $this->for)[0];
		}

		return $this->for;
	}

	/**
	 * @return string
     */
	public function getDisplayTextAttribute()
	{
		return $this->display_text();
	}

	/**
	 * @return string
     */
	public function display_text()
	{
		if (method_exists(Coanda::module($this->module()), 'generateHistoryDisplayText'))
		{
			return Coanda::module($this->module())->generateHistoryDisplayText([
				'for_id' => $this->for_id,
				'action' => $this->action,
				'data' => $this->actionData()
			]);
		}

		return $this->module() . ': ' . $this->action . ' #' . $this->for_id;
	}

}