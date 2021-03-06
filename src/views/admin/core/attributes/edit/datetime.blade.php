<div class="form-group @if (isset($invalid_fields[$attribute_identifier])) has-error @endif">
	<label class="control-label" for="attribute_{{ $attribute_identifier }}">{{ $attribute_name }} @if ($is_required) * @endif</label>

	<div class="input-group datetimepicker" data-date-format="DD/MM/YYYY HH:mm">
		<input type="text" class="date-field form-control" id="attribute_{{ $attribute_identifier }}_date" name="attributes[{{ $attribute_identifier }}]" value="{{ ($old_input ? $old_input : (isset($prefill_data) ? $prefill_data : '')) }}">
		<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
	</div>

    @if (isset($invalid_fields[$attribute_identifier]))
    	<span class="help-block">{{ $invalid_fields[$attribute_identifier] }}</span>
    @endif

</div>
