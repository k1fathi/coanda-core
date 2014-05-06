<?php namespace CoandaCMS\Coanda\Controllers\Admin;

use View, App, Coanda, Redirect, Input, Session;

use CoandaCMS\Coanda\Exceptions\ValidationException;
use CoandaCMS\Coanda\Users\Exceptions\GroupNotFound;
use CoandaCMS\Coanda\Users\Exceptions\UserNotFound;

use CoandaCMS\Coanda\Controllers\BaseController;

/**
 * Class UsersAdminController
 * @package CoandaCMS\Coanda\Controllers\Admin
 */
class UsersAdminController extends BaseController {

    /**
     * @var \CoandaCMS\Coanda\Users\Repositories\UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @param CoandaCMS\Coanda\Users\Repositories\UserRepositoryInterface $userRepository
     */
    public function __construct(\CoandaCMS\Coanda\Users\Repositories\UserRepositoryInterface $userRepository)
	{
		$this->userRepository = $userRepository;

		$this->beforeFilter('csrf', array('on' => 'post'));
	}

    /**
     * @return mixed
     */
    public function getIndex()
	{
		Coanda::checkAccess('users', 'view');

		$groups = $this->userRepository->groups();

		return View::make('coanda::admin.modules.users.index', [ 'groups' => $groups ]);
	}

    /**
     * @return mixed
     */
    public function getCreateGroup()
	{
		Coanda::checkAccess('users', 'create');

		$permissions = Coanda::availablePermissions();

		$existing_permissions = Input::old('permissions');

		$invalid_fields = Session::has('invalid_fields') ? Session::get('invalid_fields') : [];

		return View::make('coanda::admin.modules.users.creategroup', ['permissions' => $permissions, 'existing_permissions' => $existing_permissions, 'invalid_fields' => $invalid_fields ]);
	}

    /**
     * @return mixed
     */
    public function postCreateGroup()
	{
		Coanda::checkAccess('users', 'create');

		if (Input::has('cancel'))
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}

		try
		{
			$this->userRepository->createGroup(Input::all());

			return Redirect::to(Coanda::adminUrl('users'));
		}
		catch (ValidationException $exception)
		{
			return Redirect::to(Coanda::adminUrl('users/create-group'))->with('error', true)->with('invalid_fields', $exception->getInvalidFields())->withInput();
		}
	}

    /**
     * @param $group_id
     * @return mixed
     */
    public function getEditGroup($group_id)
	{
		Coanda::checkAccess('users', 'edit');

		try
		{
			$group = $this->userRepository->groupById($group_id);

			$permissions = Coanda::availablePermissions();

			$existing_permissions = Input::old('permissions', $group->access_list);

			$invalid_fields = Session::has('invalid_fields') ? Session::get('invalid_fields') : [];

			return View::make('coanda::admin.modules.users.editgroup', ['group' => $group, 'existing_permissions' => $existing_permissions, 'permissions' => $permissions, 'invalid_fields' => $invalid_fields ]);
		}
		catch (GroupNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
	}

    /**
     * @param $group_id
     * @return mixed
     */
    public function postEditGroup($group_id)
	{
		Coanda::checkAccess('users', 'edit');

		try
		{
			if (Input::has('cancel'))
			{
				return Redirect::to(Coanda::adminUrl('users'));
			}

			$this->userRepository->updateGroup($group_id, Input::all());

			return Redirect::to(Coanda::adminUrl('users'));			
		}
		catch (GroupNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
		catch (ValidationException $exception)
		{
			return Redirect::to(Coanda::adminUrl('users/edit-group/' . $group_id))->with('error', true)->with('invalid_fields', $exception->getInvalidFields())->withInput();
		}
	}

    /**
     * @param $group_id
     * @return mixed
     */
    public function getGroup($group_id)
	{
		Coanda::checkAccess('users', 'view');

		try
		{
			Session::put('last_group_view', $group_id);

			$group = $this->userRepository->groupById($group_id);

			return View::make('coanda::admin.modules.users.group', ['group' => $group ]);
		}
		catch (GroupNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
	}

    /**
     * @param $group_id
     * @return mixed
     */
    public function getCreateUser($group_id)
	{
		Coanda::checkAccess('users', 'create');

		try
		{
			$group = $this->userRepository->groupById($group_id);
			$invalid_fields = Session::has('invalid_fields') ? Session::get('invalid_fields') : [];

			return View::make('coanda::admin.modules.users.createuser', ['group' => $group, 'invalid_fields' => $invalid_fields ]);
		}
		catch (GroupNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
	}

    /**
     * @param $group_id
     * @return mixed
     */
    public function postCreateUser($group_id)
	{
		Coanda::checkAccess('users', 'create');

		if (Input::has('cancel'))
		{
			return Redirect::to(Coanda::adminUrl('users/group/' . $group_id));
		}

		try
		{
			$this->userRepository->createNew(Input::all(), $group_id);

			return Redirect::to(Coanda::adminUrl('users/group/' . $group_id));
		}
		catch (ValidationException $exception)
		{
			return Redirect::to(Coanda::adminUrl('users/create-user/' . $group_id))->with('error', true)->with('invalid_fields', $exception->getInvalidFields())->withInput();
		}
		catch (GroupNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
	}

    /**
     * @param $user_id
     * @return mixed
     */
    public function getEditUser($user_id)
	{
		Coanda::checkAccess('users', 'edit');

		try
		{
			$user = $this->userRepository->find($user_id);

			$invalid_fields = Session::has('invalid_fields') ? Session::get('invalid_fields') : [];

			return View::make('coanda::admin.modules.users.edituser', ['user' => $user, 'invalid_fields' => $invalid_fields ]);
		}
		catch (UserNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
	}

    /**
     * @param $user_id
     * @return mixed
     */
    public function postEditUser($user_id)
	{
		Coanda::checkAccess('users', 'edit');

		$last_group_id = Session::get('last_group_view');

		if (Input::has('cancel'))
		{
			return Redirect::to(Coanda::adminUrl('users/group/' . $last_group_id));
		}

		try
		{
			$user = $this->userRepository->find($user_id);

			$this->userRepository->updateExisting($user_id, Input::all());

			return Redirect::to(Coanda::adminUrl('users/group/' . $last_group_id));
		}
		catch (ValidationException $exception)
		{
			return Redirect::to(Coanda::adminUrl('users/edit-user/' . $user_id))->with('error', true)->with('invalid_fields', $exception->getInvalidFields())->withInput();
		}
		catch (UserNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users/group/' . $last_group_id));
		}
	}

    /**
     * @param $user_id
     * @param string $selected_tab
     * @return mixed
     */
    public function getUser($user_id, $selected_tab = '')
	{
		Coanda::checkAccess('users', 'view');

		$last_group_id = Session::get('last_group_view');

		try
		{
			$user = $this->userRepository->find($user_id);

			return View::make('coanda::admin.modules.users.user', ['user' => $user, 'selected_tab' => $selected_tab ]);
		}
		catch (UserNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users/group/' . $last_group_id));
		}
	}

    /**
     * @param $user_id
     * @param $group_id
     * @return mixed
     */
    public function getAddToGroup($user_id, $group_id)
	{
		Coanda::checkAccess('users', 'edit');

		try
		{
			$this->userRepository->addUserToGroup($user_id, $group_id);

			return Redirect::to(Coanda::adminUrl('users/user/' . $user_id . '/groups'));
		}
		catch (UserNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
		catch (GroupNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
	}

    /**
     * @param $user_id
     * @param $group_id
     * @return mixed
     */
    public function getRemoveFromGroup($user_id, $group_id)
	{
		Coanda::checkAccess('users', 'edit');

		try
		{
			$this->userRepository->removeUserFromGroup($user_id, $group_id);

			return Redirect::to(Coanda::adminUrl('users/user/' . $user_id . '/groups'));
		}
		catch (UserNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
		catch (GroupNotFound $exception)
		{
			return Redirect::to(Coanda::adminUrl('users'));
		}
	}	
}