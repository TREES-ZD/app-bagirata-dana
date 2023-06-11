<div class="text-center">
    
    <a style="font-size: revert" class="label label-success">{{$data}}</a>
    @if (isset($failed_data) && $failed_data != 0)
        <a style="font-size: revert" class="label label-danger">{{$failed_data}}</a>
        <span style="font-size: revert"> ({{($data + $failed_data) ? round(100*($failed_data / ($data + $failed_data))) : 0}}% fail rate)</span>        
    @endif
   
</div>