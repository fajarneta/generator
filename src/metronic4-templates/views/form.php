@extends(Request::get('ajax') ? 'layouts.modal':'layouts.content')

@section('title', $title=($<?=$gen->modelVariableName()?>->exists?'Edit':'Tambah').' <?=$gen->titlePlural()?>')

@section('sub-content')

		{{ Form::model($<?=$gen->modelVariableName()?>,array('route' => array((!$<?=$gen->modelVariableName()?>->exists) ? '<?=strtolower($gen->moduleName)?>.<?=$gen->route()?>.store':'<?=strtolower($gen->moduleName)?>.<?=$gen->route()?>.update',$<?=$gen->modelVariableName()?>->pk()),
	        'class'=>'form-horizontal form-bordered','id'=>'<?=$gen->route()?>-form','method'=>(!$<?=$gen->modelVariableName()?>->exists) ? 'POST' : 'PUT')) }}

		<div class="modal-body">
			<div class="panel-body panel-body-nopadding" style="display: block;">
	            <div class="col-md-12 col-sm-12">
<?php foreach ( $fields as $field )  { ?>
<?php if( $str = \App\Generator\src\DbOracle::getFormInputMarkup( $field, $gen->modelVariableName() ) ) { ?>
        <?=$str?>
<?php } ?>
<?php } ?>

	           	</div>
	        </div>
		</div>

		<div class="modal-footer panel-footer">
			@if(Request::get('ajax'))<button type="button" class="btn default" data-dismiss="modal">Tutup</button>@endif
			<button class="btn blue">Simpan</button>
		</div>

	    {{ Form::close() }}

@endsection

@push('js')
<script type="text/javascript">
$(document).ready(function(){
	var rule=defaultValidation;

	rule.rules = {
	<?php
		$jsScript = "";
		$jsScriptAutocomplete = "";
		$timestampType = false;
		foreach ( $fields as $field )  {
			if($field->type=="timestamp"){ $timestampType=true; }
			if(in_array($field->name,\App\Generator\src\DbOracle::skippedFields()) && $field->name!='created_at' && $field->name!='updated_at') continue;
				if($field->required){
					$jsScript.= $field->name;
					$jsScript.= " : { ";
					$jsScript.= $field->required?"required: true":'';
					$jsScript.=" }, ";
				}

				if(($field->type == 'number')&&(preg_match("/id_|_id/",$field->name,$match))){
					$fieldName = strtolower(preg_replace("/id_|_id/","",$field->name));
					$temp ='
					var '.$fieldName.'Engine = new Bloodhound({
							datumTokenizer: function(d) { return d.tokens; },
							queryTokenizer: Bloodhound.tokenizers.whitespace,
							cache: false,
							remote: {
								url: \'{{ url("'.strtolower($gen->moduleName).'/autocomplete/'.$fieldName.'") }}?q=%QUERY\',
								wildcard: "%QUERY"
							}
						});
						'.$fieldName.'Engine.initialize();

						$("#'.$fieldName.'").typeahead({
									hint: true,
									highlight: true,
									minLength: 1
							},
							{
									source: '.$fieldName.'Engine.ttAdapter(),
									name: "'.$fieldName.'",
									displayKey: "'.$fieldName.'",
									templates: {
										suggestion: function(data){
											return Handlebars.compile([
															"<div class=\"media\">",
																		"<div class=\"media-body\">",
																				"<h5 class=\"media-heading\">@{{'.$fieldName.'}}</h5>",
																		"</div>",
															"</div>",
														].join(""))(data);
										},
											empty: [
													"<div class=\"empty-message\">'.$fieldName.' tidak ditemukan</div>"
											]
									}
							}).bind("typeahead:selected", function(obj, datum, name) {
								$("#'.$field->name.'").val(datum.id);
							}).bind("typeahead:change", function(obj, datum, name) {

							});
					';
					$jsScriptAutocomplete .=$temp;
				}
		}
		$jsScript=rtrim($jsScript, ",");
		echo $jsScript."\n";
	?>
	};
	$('#<?=$gen->route()?>-form').validate(rule);
	<?php if($timestampType) {?>
	$('.tanggal-picker').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight: true});
	<?php
	}
	echo $jsScriptAutocomplete."\n";
	?>

});
</script>
@endpush
