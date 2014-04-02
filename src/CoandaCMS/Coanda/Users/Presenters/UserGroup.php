<?php namespace CoandaCMS\Coanda\Users\Presenters;

use Lang;

class UserGroup extends \CoandaCMS\Coanda\Core\Presenters\Presenter {

	public function permissions()
	{
		$access_list = $this->model->access_list;
		
		if (is_array($access_list) && $access_list[0] == '*')
		{
			return 'all';
		}

		$permission_list = [];

		foreach ($access_list as $module => $permissions)
		{
			$permission_list[ucfirst($module)] = [];

			foreach ($permissions as $permission)
			{
				$permission_list[ucfirst($module)][] = ucfirst($permission);
			}
		}

		return $permission_list;
	}

}