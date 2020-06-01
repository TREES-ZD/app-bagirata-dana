<?php

Namespace App\Admin\Actions\Task;

Use App\Models\Document;
use App\Task;
use Encore\Admin\Actions\GridAction;
Use Encore\Admin\Actions\RowAction;

class RunTask extends \Encore\Admin\Actions\RowAction
{
    public function render()
    {
        $model = $this->getRow();
        return '<a href="/backend/tasks/sync"><i class="fa fa-refresh"></i> Sync</a>';
    }
}