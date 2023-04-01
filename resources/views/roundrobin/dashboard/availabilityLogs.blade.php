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
            <tr class="{{$availabilityLog->status == 'Available' ? 'success' : 'danger' }}">
                <td >{{ $availabilityLog->created_at }}</td>
                <td>{{ $availabilityLog->status }}</td>
                <td>{{ $availabilityLog->agent_name }}</td>
            </tr>
        @endforeach
    </tbody>
  </table>