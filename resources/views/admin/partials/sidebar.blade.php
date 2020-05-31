<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel (optional) -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="{{ Admin::user()->avatar }}" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
                <p>{{ Admin::user()->name }}</p>
                <!-- Status -->
                <a href="#"><i class="fa fa-circle text-success"></i> {{ trans('admin.online') }}</a>
            </div>
        </div>

        @if(config('admin.enable_menu_search'))
        <!-- search form (Optional) -->
        <form class="sidebar-form" style="overflow: initial;" onsubmit="return false;">
            <div class="input-group">
                <input type="text" autocomplete="off" class="form-control autocomplete" placeholder="Search...">
              <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
                <ul class="dropdown-menu" role="menu" style="min-width: 210px;max-height: 300px;overflow: auto;">
                    @foreach(Admin::menuLinks() as $link)
                    <li>
                        <a href="{{ admin_url($link['uri']) }}"><i class="fa {{ $link['icon'] }}"></i>{{ admin_trans($link['title']) }}</a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </form>
        <!-- /.search form -->
        @endif

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="header">{{ trans('admin.menu') }}</li>
            <!-- @each('admin::partials.menu', Admin::menu(), 'item') -->
            <li><a href="/admin"><i class="fa fa-home"></i><span>Dashboard</span></a>
            <li><a href="/admin/agents"><i class="fa fa-users"></i><span>Agents</span></a>
            <li><a href="/admin/rules"><i class="fa fa-bars"></i><span>Rules</span></a>
            <li><a href="/admin/tasks"><i class="fa fa-play"></i><span>Tasks</span></a>
            <li><a href="/admin/assignment_logs"><i class="fa fa-bars"></i><span>Assignment Logs</span></a></li>
            <li><a href="/admin/availability_logs"><i class="fa fa-bars"></i><span>Availability Logs</span></a></li>
        </ul>
        <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>