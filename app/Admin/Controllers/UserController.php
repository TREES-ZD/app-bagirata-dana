<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Services\ZendeskService;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\AdminController;

class UserController extends AdminController
{
    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('admin.administrator');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        \Encore\Admin\Auth\Permission::check('administrator');
        
        $userModel = config('admin.database.users_model');

        $grid = new Grid(new $userModel());

        $grid->column('id', 'ID')->sortable();
        $grid->column('username', trans('admin.username'));
        $grid->column('name', trans('admin.name'));
        $grid->column('roles', trans('admin.roles'))->pluck('name')->label();
        $grid->column('zendesk_assignee_ids', "Assignees")->label();        
        $grid->column('zendesk_group_ids', "Groups")->label();
        $grid->column('zendesk_custom_field_ids', "Custom Fields")->label();
        $grid->column('created_at', trans('admin.created_at'));
        $grid->column('updated_at', trans('admin.updated_at'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
            }
        });
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableColumnSelector();
        
        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        \Encore\Admin\Auth\Permission::check('administrator');

        $userModel = config('admin.database.users_model');

        $show = new Show($userModel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('username', trans('admin.username'));
        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles->pluck('name');
        })->label();    
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission->pluck('name');
        })->label();
        $show->field('zendesk_assignee_ids', "Assignees");        
        $show->field('zendesk_group_ids', "Groups");
        $show->field('zendesk_custom_field_ids', "Custom Fields");
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        \Encore\Admin\Auth\Permission::check('administrator');

        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->disableViewCheck();    
        $form->tools(function ($tools) {
            $tools->disableView();
        });

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        // $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        $form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));
        $form->multipleSelect('zendesk_assignee_ids', "Assignees")->options(app(ZendeskService::class)->getUsersByKey(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY)->toArray());
        $form->multipleSelect('zendesk_group_ids', "Groups")->options(app(ZendeskService::class)->getGroupsByKey(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY)->toArray());
        $form->multipleSelect('zendesk_custom_field_ids', "Custom Fields")->options(app(ZendeskService::class)->getCustomFieldsByValue(ZendeskService::ALL, ZendeskService::SHOW_NAME_ONLY)->toArray());

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = bcrypt($form->password);
            }
        });

        return $form;
    }
}
