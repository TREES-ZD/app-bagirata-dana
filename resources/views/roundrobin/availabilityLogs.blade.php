<div class="box">
    @if(isset($title))
    <div class="box-header with-border">
        <h3 class="box-title"> {{ $title }}</h3>
    </div>
    @endif

    @if ( $grid->showTools() || $grid->showExportBtn() || $grid->showCreateBtn() )
    <div class="box-header with-border">
        <div class="pull-right">
            {!! $grid->renderColumnSelector() !!}
            {!! $grid->renderExportButton() !!}
            {!! $grid->renderCreateButton() !!}
            @if (isset($customActions))
                @foreach($customActions as $action)
                        {!! $action !!}
                @endforeach
            @endif
        </div>

        <div class="pull-left">
            {!! $grid->renderHeaderTools() !!}
        </div>
        
    </div>
    @endif

    {!! $grid->renderFilter() !!}

    {!! $grid->renderHeader() !!}

    <!-- /.box-header -->
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover" id="{{ $grid->tableID }}">
            <thead>
                <tr>
                    <th>Agent Name</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Previous Status</th>
                    <th>Previous Time</th>
                    <th>Time gap</th>
                </tr>
            </thead>

            @if ($grid->hasQuickCreate())
                {!! $grid->renderQuickCreate() !!}
            @endif

            <tbody>

                @if($results->isEmpty())
                    @include('admin::grid.empty-grid')
                @endif

                @foreach($results as $row)
                <tr>
                   <td>{{$row->agent_name}}</td>
                   <td>{{$row->custom_status}}</td>
                   <td>{{$row->start_time}}</td>
                   <td>{{$row->previous_status}}</td>
                   <td>{{$row->previous_time}}</td>
                   <td>{{$row->time_gap}}</td>
                </tr>
                @endforeach
            </tbody>

            {!! $grid->renderTotalRow() !!}

        </table>

    </div>

    {!! $grid->renderFooter() !!}

    <div class="box-footer clearfix">
        {!! $pagination->render('admin::pagination') !!}
    </div>
    <!-- /.box-body -->
</div>