<?php

Namespace App\Admin\Actions\Task;

use App\Models\Task;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchDeleteTask extends BatchAction
{
    public $name = 'Delete';

    public function handle(Collection $collection)
    {
        Task::whereIn('id', $collection->pluck('id'))->delete();

        return $this->response()->success('Success deleting ' . $collection->count() . ' task(s)')->refresh();
    }

}