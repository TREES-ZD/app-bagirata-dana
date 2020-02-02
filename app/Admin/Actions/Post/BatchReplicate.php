<?php

namespace App\Admin\Actions\Post;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchReplicate extends BatchAction
{
    public $name = 'batch copy';

    public function handle(Collection $collection)
    {
        foreach ($collection as $model) {
            
        }

        return $this->response()->success('Success message...')->refresh();
    }

    public function form()
    {
        $type = [
            1 => 'Advertising',
            2 => 'Illegal',
            3 => 'Fishing',
        ];
    
        $this->checkbox('type', 'type')->options($type);
        $this->textarea('reason', 'reason')->rules('required');
    }    

    
}