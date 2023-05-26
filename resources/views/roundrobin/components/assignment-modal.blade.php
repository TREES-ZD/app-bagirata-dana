 <span data-toggle="modal" data-target="#grid-modal-{{ $name }}" data-key="{{ $key }}">
    <a href="javascript:void(0)"><i class="fa fa-times text-red"></i> detail</a>
</span>  

<div class="modal grid-modal fade" id="grid-modal-{{ $name }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 5px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Response Details</h4>
            </div>
            <div class="modal-body">
                {{ $response_details }}
            </div>
        </div>
    </div>
</div>


<style>
    .box.grid-box {
        box-shadow: none;
        border-top: none;
    }

    .grid-box .box-header:first-child {
        display: none;
    }
</style>
