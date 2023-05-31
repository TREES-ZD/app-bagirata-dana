<select name='select-{{ $name }}' class="form-control ie-input" data-key={{$key}}>
@foreach($options as $option => $label)
    <option name='select-{{ $name }}' value="{{ $option }}" data-label="{{ $label }}" {{ $option == $value ? 'selected' : ''}}>&nbsp;{{$label}}&nbsp;&nbsp;</option>
@endforeach
</select>

<script>
    console.log("hddai");
    $('select[name="select-{{ $name }}"]').change(function() {
        // Get the selected option value
        var selectedOption = $(this).val();
        {{--  console.log(selectedOption);  --}}
        // Make an API call with the selected value
        var key = $(this).data('key');
        var value = $(this).val();
        var _status = true;
        console.log(selectedOption, key, value)

        var checkedRows = Array.from(document.querySelectorAll('tbody .icheckbox_minimal-blue.checked')).map(e => $(e).parents(':nth(1)'));
        checkedRows.forEach(el => el[0].querySelector('.column-custom_status select').value = value);

        var ids = checkedRows.length ? [...new Set([...checkedRows.map(e => e.data('key')), key])] : [key];
        $.ajax({
            url: "{{route('agent.updateBulk')}}",
            type: "POST",
            async:false,
            data: {
                "{{ $name }}": value,
                "ids": ids,
                _token: LA.token,
                _method: 'PUT',
                _edit_inline: true
            },
            success: function (data) {
                if (data.status)
                    toastr.success(data.message);
                else
                    toastr.warning(data.message);
            },
            error: function (xhr, textStatus, errorThrown) {
                _status = false;
                var data = xhr.responseJSON
                if (data['errors'] || data['message']) {
                    var message = data['message'] || Object.values(data['errors']).join("\n");
                    toastr.error(message);
                } else {
                    toastr.error('Error: ' + errorThrown);
                }
            },
            complete:function(xhr,status) {
                if (status == 'success')
                    _status = xhr.responseJSON.status;
            }
        });

    });
</script>