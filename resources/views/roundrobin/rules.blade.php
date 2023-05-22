

    {{--  <table class="table table-bordered" id="users-table">
        <thead>
            <tr>
                <th>Agent</th>
                @foreach ($tasks as $task)
                    <th>{{$task->zendesk_view_title}}</th>             
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($agents as $agent)
                <tr>
                    <td>{{$agent->fullName}}</td>
                    <td>
                        <a href="" class="update" data-name="name" data-type="text" data-pk="{{ $agent->id }}" data-title="Enter name">{{ $agent->fullName }}</a>
                    </td>
                </tr>                
            @endforeach
        </tbody>
    </table>   --}}
 <table id="my-table" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Row</th>
                    @foreach($tasks as $task)
                        <th>{{ $task->zendesk_view_title }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($agents as $agent)
                    <tr>
                        <td>{{ $agent->fullName }}</td>
                        @foreach($columns as $column)
                            <td data-row="{{ $row->id }}" data-column="{{ $column->id }}" data-value="{{ $matrix[$row->id][$column->id] }}">{{ $matrix[$row->id][$column->id] }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>   

<script>
    $(function() {    
        $.fn.editable.defaults.mode = 'inline';

        $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            "columnDefs": [
                { className: "highlight", targets: "_all" }
            ],
            fixedColumns:   {
                left: 12,
                right: 0
            }
        });


    });
</script>

