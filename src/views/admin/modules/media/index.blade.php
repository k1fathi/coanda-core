@extends('coanda::admin.layout.main')

@section('page_title', 'Media')

@section('content')

<div class="row">
	<div class="breadcrumb-nav">
		<ul class="breadcrumb">
			<li><a href="{{ Coanda::adminUrl('media') }}">Media</a></li>
		</ul>
	</div>
</div>

<div class="row">
	<div class="page-name col-md-12">
		<h1 class="pull-left">Media</h1>
		<div class="page-status pull-right">
			<span class="label label-default">Total {{ $media_list->getTotal() }}</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="page-options col-md-12">
		@if (Coanda::canView('media', 'create'))
			<a href="{{ Coanda::adminUrl('media/add') }}" class="btn btn-primary">Add media</a>
		@else
			<span class="btn btn-primary" disabled="disabled">Add media</span>
		@endif
	</div>
</div>

<div class="row">
	<div class="col-md-8">
		<div class="page-tabs">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#media" data-toggle="tab">Media</a></li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="media">

					@if (Session::has('media_uploaded'))
						<div class="alert alert-success">
							{{ Session::get('media_uploaded_message') }} successfully uploaded.
						</div>
					@endif

					@if ($media_list->count() > 0)
						<div class="row">
							@foreach ($media_list as $media)
								<div class="col-md-2 col-xs-3">
									<div class="thumbnail">
										<a href="{{ Coanda::adminUrl('media/view/' . $media->id) }}">
											@if ($media->type == 'image')
												<img src="{{ url($media->cropUrl(100)) }}" width="100" height="100">
											@else
												<img src="{{ asset('packages/coandacms/coanda-core/images/file.png') }}" width="100" height="100">
											@endif
										</a>
										<div class="caption"><a href="{{ Coanda::adminUrl('media/view/' . $media->id) }}">{{ $media->present()->name }}</a></div>
									</div>
								</div>
							@endforeach
						</div>

						{{ $media_list->links() }}
					@else
						<p>No media in the system yet, be the first to upload!</p>
					@endif

				</div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="page-tabs">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#upload" data-toggle="tab">Upload</a></li>
				<li><a href="#tags" data-toggle="tab">Tags</a></li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="upload">

					@if (Coanda::canView('media', 'create'))
						<p>Max file size <span class="label label-info">{{ $max_upload }}</span></p>
						<form action="{{ Coanda::adminUrl('media/handle-upload') }}" class="dropzone" id="dropzone-uploader" data-reload-url="{{ Coanda::adminUrl('media') }}"></form>
					@else
						<p>You do not have permission to add media</p>
					@endif
				</div>
				<div class="tab-pane" id="tags">

					<p>
						@foreach ($tags as $tag)
							<a href="{{ Coanda::adminUrl('media/tag/' . $tag->id) }}"><i class="fa fa-tag"></i> {{ $tag->tag }} ({{ $tag->media->count() }})</a>
							&nbsp;
						@endforeach
					</p>

					<p><a href="{{ Coanda::adminUrl('media/tags') }}">View all tags</a></p>
				</div>
			</div>
		</div>
	</div>
</div>

@stop
