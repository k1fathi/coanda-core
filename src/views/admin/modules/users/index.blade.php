@extends('coanda::admin.layout.main')

@section('page_title', 'Users')

@section('content')

<div class="row">
	<div class="breadcrumb-nav">
		<ul class="breadcrumb">
			<li><a href="{{ Coanda::adminUrl('users') }}">Users</a></li>
		</ul>
	</div>
</div>

<div class="row">
	<div class="page-name col-md-12">
		<h1 class="pull-left">User Groups</h1>
		<div class="page-status pull-right">
			<span class="label label-default">Total {{ $groups->count() }}</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="page-options col-md-12">
		@if (Coanda::canView('users', 'create'))
			<a href="{{ Coanda::adminUrl('users/create-group') }}" class="btn btn-primary">New group</a>
		@else
			<span class="btn btn-primary" disabled="disabled">New group</span>
		@endif
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="page-tabs">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#groups" data-toggle="tab">User Groups</a></li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="subpages">

					@if (Session::has('user_deleted'))
						<div class="alert alert-danger">
							{{ Session::get('user_name') }} deleted
						</div>
					@endif

					@if (Session::has('group_deleted'))
						<div class="alert alert-danger">
							{{ Session::get('group_name') }} user group was deleted
						</div>
					@endif

					<table class="table table-striped">
						@foreach ($groups as $group)
							<tr>
								<td>
									<i class="fa fa-users"></i>
									<a href="{{ Coanda::adminUrl('users/group/' . $group->id) }}">{{ $group->name }}</a>
								</td>
								<td>{{ $group->users->count() }} user{{ $group->users->count() !== 1 ? 's' : '' }}</td>
								<td class="tight">
									@if (Coanda::canView('users', 'edit'))
										<a href="{{ Coanda::adminUrl('users/edit-group/' . $group->id) }}"><i class="fa fa-pencil-square-o"></i></a>
									@else
										<span class="disabled"><i class="fa fa-pencil-square-o"></i></span>
									@endif
								</td>
							</tr>
						@endforeach
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

@stop
