<div class="text-center">
    
    <a style="font-size: medium" class="label label-success">{{$data}}</a>
        <a style="font-size: medium" class="label label-danger">{{$failed_data}}</a>
        <span style="font-size: larger"> ({{($data + $failed_data) ? round(100*($failed_data / ($data + $failed_data))) : 0}}% fail rate)</span>

   
</div>