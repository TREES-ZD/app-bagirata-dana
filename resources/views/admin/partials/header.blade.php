<!-- Main Header -->
<header class="main-header">

    <!-- Logo -->
    <a href="{{ admin_url('/') }}" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span id="bagirata-logo-mini" class="logo-mini" >{!! file_get_contents('images/bagirata-logo-notext.svg') !!}</span>
        <!-- logo for regular state and mobile devices -->
        <span id="bagirata-logo-lg" class="logo-lg">{!! file_get_contents('images/bagirata-logo.svg') !!}</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <ul class="nav navbar-nav hidden-sm visible-lg-block">
        {!! Admin::getNavbar()->render('left') !!}
        </ul>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

                <!-- {!! Admin::getNavbar()->render() !!} -->
                <li>
                    <a href="/refresh" class="container-refresh">
                    <i class="fa fa-refresh"></i>
                    </a>
                </li>

                <!-- User Account Menu -->
                <li class="dropdown user user-menu">
                    <!-- Menu Toggle Button -->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <!-- The user image in the navbar-->
                        <img src="{{ Admin::user()->avatar }}" class="user-image" alt="User Image">
                        <!-- hidden-xs hides the username on small devices so only the image appears. -->
                        <span class="hidden-xs">{{ Admin::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- The user image in the menu -->
                        <li class="user-header">
                            <img src="{{ Admin::user()->avatar }}" class="img-circle" alt="User Image">

                            <p>
                                {{ Admin::user()->name }}
                                <small>Member since admin {{ Admin::user()->created_at }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                        @if (Admin::user()->isAdministrator())
                            <div class="pull-left">
                                <a href="{{ admin_url('auth/users') }}" class="btn btn-default btn-flat">Users</a>
                            </div>
                        @endif
                            <div class="pull-right">
                                <a href="{{ admin_url('auth/logout') }}" class="btn btn-default btn-flat">{{ trans('admin.logout') }}</a>
                            </div>
                        </li>
                    </ul>
                </li>
                <!-- Control Sidebar Toggle Button -->
                {{--<li>--}}
                    {{--<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>--}}
                {{--</li>--}}
            </ul>
        </div>
    </nav>
</header>