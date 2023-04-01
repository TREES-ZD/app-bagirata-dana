<table class="table table-striped">
    <thead>
    <tr>
      <th scope="col">Time</th>
      <th scope="col">Agent</th>
      <th scope="col">View ID</th>
      <th scope="col">Ticket ID</th>
      <th scope="col">Subject</th>
      <th scope="col">Type</th>
    </tr>
    </thead>
    <tbody>
        @foreach ($latestAssignments as $assignment)
            <tr>
                <td>{{ $assignment['created_at'] }}</td>
                <td>{{ $assignment['agent_name'] }}</td>
                <td>{{ is_numeric($assignment['zendesk_view_id']) ? $assignment['zendesk_view_id'] : '' }}</td>
                <td>{{ $assignment['zendesk_ticket_id'] }}</td>
                <td>{{ $assignment['zendesk_ticket_subject'] }}</td>
                <td>{{ $assignment['type'] }}</td>
            </tr>
        @endforeach
    </tbody>
  </table>