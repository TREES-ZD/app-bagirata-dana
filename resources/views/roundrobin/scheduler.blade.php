

    <table class="table table-bordered" id="users-table">
        <thead>
            <tr>
                <th>status</th>
                <th>name</th>
                <th>failed_at</th>
            </tr>
        </thead>
    </table>    

<script>
    $(function() {
        $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/backend/logsTable',
            columns: [
                { data: 'status', name: 'Status' },
                { data: 'name', name: 'Name' },
                { data: 'failed_at', name: 'Failed at' }
            ]
        });
    });
</script>

