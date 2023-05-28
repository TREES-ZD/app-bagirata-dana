<table class="table">
    <thead>
    <tr>
      <th scope="col">Time</th>
      <th scope="col">Status</th>
      <th scope="col">Agent</th>
    </tr>
    </thead>
    <tbody>
        @foreach ($availabilityLogs as $availabilityLog)
            <tr class="{{$availabilityLog->custom_status == 'AVAILABLE'? 'success' : ($availabilityLog->custom_status == 'AWAY' ? 'warning' : 'danger') }}">
                <td >{{ $availabilityLog->created_at }}</td>
                <td>{{ $availabilityLog->custom_status ?? ($availabilityLog->status ? 'AVAILABLE' : 'UNAVAILABLE' ) }}</td>
                <td>{{ $availabilityLog->agent_name }}</td>
            </tr>
        @endforeach
    </tbody>
  </table>