@extends('layouts.fulljqx')
@section('page_heading',Baset\getMenuContent('backup'))
@section('page_heading_class','col-lg-8')
@section('page_heading_extra')
		<button type="button" class="btn btn-default btn-xs b-fix-header-margin-more" style="width: 50px;" onclick="reloadAll()">
			<span class="glyphicon glyphicon-refresh"></span>
		</button>
@stop

@section('section')

	<div class="col-lg-8">
@permission('backup-upload')
		<div class="margin-bottom-7">
			{!! Form::open(array('url'=>'backup/upload','method'=>'POST', 'files'=>true, 'id'=>'myForm')) !!}
			{!! Form::file('file', array('class'=>'hidden', 'id' => 'form-file')) !!}
			{!! Form::submit('Submit', array('class'=>'hidden', 'id'=>'submit_upload_prim')) !!}
			{!! Form::close() !!}

			<span id="uploadMessage" class="text-success" hidden style="margin-left: 50px;">message</span>
			<div class="input-group">
				<span class="input-group-addon"><span class="fa fa-upload fa-fw" style="width: 135px;"> Upgrade</span></span>
				<p id="selectedFile" class="form-control" style="overflow: hidden;"></p>
				<span class="input-group-btn">
					<button type="button" class="btn btn-primary" style="width: 106px;"
							onclick="$('#form-file').click();">
							<span class="glyphicon glyphicon-folder-open"></span> Browse&hellip;
					</button>
					<button id="submit_upload" type="button" class="btn btn-primary round-7" disabled style="width: 106px;"
							onclick="$('#submit_upload_prim').click()">
						<span class="glyphicon glyphicon-open"></span> Upload
					</button>
				</span>
			</div>
		</div>
@endpermission
@permission(['backup-download','rpc-general-RestoreSettings'])
		<div class="margin-bottom-15">
			<div class="input-group">
				<span class="input-group-addon"><span class="fa fa-download fa-fw" style="width: 90px;"> Download</span></span>
				<span class="input-group-addon"><div id="selectedId" style="width: 20px;"></div></span>
				<p id="selectedItem" class="form-control" style="overflow: hidden; "></p>
				<span class="input-group-btn">
@permission('backup-download')
					<button type="button" class="btn btn-success" style="width: 106px;" onclick="downloadBackup()">
						<span class="glyphicon glyphicon-download-alt"></span> Download
					</button>
@endpermission
@permission('rpc-general-RestoreSettings')
					<button type="button" class="btn btn-info round-7" style=" width: 106px;"
							data-toggle="modal" data-target="#modalRestore">
						<span class="glyphicon glyphicon-transfer"></span> Restore
					</button>
@endpermission
				</span>
			</div>
		</div>
@endpermission
	<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 class="panel-title">
					<a class="accordion-toggle" data-parent="#accordion" data-toggle="collapse"
					   href="#collapse_id">List of Available Backups</a>
				</h4>
			</div>

			<div id="collapse_id" class="panel-collapse collapse in">
				<div class="panel-body">
					<div style="margin: -15px -13px -15px -15px;">
						<div id="gridParams"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
@permission('rpc-general-RestoreSettings')
	@include('widgets.baset.modal', array('id'=> 'modalRestore',
								'header'=>'Confirm Restore',
								'message'=>"<p>You are about to restore the selected backup.</p><p>Do you want to proceed?</p>",
								'confirm'=>'Restore',
								'function'=>'confirmRestore()'))
@endpermission
@stop

@section('scripts-bottom')
	@parent
	<script type="text/javascript">
		function reloadAll() {
			grid.initData()
		}
@permission('backup-upload')
			$('#myForm').submit(function(e){
			e.preventDefault();

			var files = $("#form-file").prop("files");
			if(files.length <= 0)
			{
				b_notify('warning', ' Please select a file first');
				return;
			}

			var form_data = new FormData();
			form_data.append("file", files[0]);

			postUpload('{{url("/backup/upload")}}', form_data, function (succeeded, respond) {
				$('#uploadMessage').html(respond.message);
				var cl1 = 'text-success';
				var cl2 = 'text-danger';
				if(respond.result == 3)
				{
					var tmp = cl1;
					cl1 = cl2;
					cl2 = tmp;
					$('#submit_upload').prop('disabled', true);
					$('#selectedFile').html('');
					reloadAll();
				}
				$('#uploadMessage').removeClass(cl1).addClass(cl2);
				$('#uploadMessage').show();
			});
		});

		$(function () {
			$(document).on('change', ':file', function () {
				var input = $(this),
						numFiles = input.get(0).files ? input.get(0).files.length : 1,
						label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
				input.trigger('fileselect', [numFiles, label]);
			});
			$(document).ready(function () {
				$(':file').on('fileselect', function (event, numFiles, label) {
					$('#uploadMessage').hide();
					$('#selectedFile').html(label);
					$('#submit_upload').prop('disabled', false);
				});
			});
		});
@endpermission

@permission('backup-download')
		function downloadBackup() {
			window.location = "/backup/download/" + $('#selectedId').html();
		}
@endpermission

@permission('rpc-general-RestoreSettings')
		function confirmRestore() {
			post('{{url("/rpc-general/RestoreSettings/").'/'}}' + $('#selectedId').html(), '', function (succeeded, respond) {
				$('#modalRestore').modal('hide');
			});
		}
@endpermission
		var grid = null;
		<?php $model = 'Backup' ?>
		$(document).ready(function () {
			var g = new basetGrid('gridParams');
			grid = g;
			var s = g.settings;
            s.addIdColumn('id');
			s.model = '{{$model}}';
			s.height = 'auto';
			s.autorowheight = s.autoheight = s.pageable = true;
			var t = s.toolbar;
			t.cozy = true;
			t.showAdd  = t.showSearch = t.showExpanders = s.rowsHaveDetails = false;

			@if(!Auth::user()->can("model-$model-delete"))
					t.enabled = false;
					t.showDelete = t.showSelect = false;
			@endif
			s.sortColumn = 'id';
			s.sortDirection = 'dec';
			s.onReady = function () {
				g.grid.jqxGrid('clearselection');
				g.grid.jqxGrid('selectrow', g.grid.jqxGrid('getrowboundindex', 0));
			};
			// g.settings.idColumns = ['id'];
			g.settings.columns = [
				g.textColumnValidate('Title', 'title', 'auto', '{{Auth::user()->can('model-Backup-update')}}'),
				g.textColumnValidate('Backup Time', 'created', '140', '{{Auth::user()->can('model-Backup-update-all-columns')}}')
			];

			g.ready();

			g.grid.on('rowselect', function (event) {
				var rowData = event.args.row;
				if(!rowData)
					return;
				$('#selectedItem').html(printf("%s - <span style='font-style: italic;'>%s</span>", rowData.title, rowData.created));
				$('#selectedId').html(printf("%s", rowData.id));
			});
		});

	</script>

@endsection


