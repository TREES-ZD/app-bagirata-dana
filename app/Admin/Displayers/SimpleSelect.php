<?php

namespace App\Admin\Displayers;

use Encore\Admin\Admin;
use Illuminate\Support\Arr;
use Encore\Admin\Grid\Displayers\AbstractDisplayer;

class SimpleSelect extends AbstractDisplayer
{
    public function display($options = [])
    {
        return Admin::component('roundrobin.admin.simple-select', [
            'key'      => $this->getKey(),
            'value'    => $this->getValue(),
            'name'     => $this->getPayloadName(),
            'options' => $options
        ]);
    }
}
