<div class="col-md-12">
    <div class="input-group" id="adv-search">
        <input type="text" class="form-control" placeholder="Filter" />
        <div class="input-group-btn">
            <div class="btn-group" role="group">
                <div class="dropdown dropdown-lg">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                        aria-expanded="false"><span class="caret"></span></button>
                    <div class="dropdown-menu dropdown-menu-right" role="menu">
                        <form class="form-horizontal" role="form">
                            <div class="form-group">
                                <label for="filter">Assignee</label>
                                <select class="form-control">
                                    <option value="0" selected>All</option>
                                    <option value="support">Norman</option>
                                    <option value="poc1demo">poc1demo</option>
                                    <option value="poc1demo">Diastowo Faryduana</option>
                                    <option value="poc1demo">Luhung</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filter">Group</label>
                                <select class="form-control">
                                    <option value="0" selected>All</option>
                                    <option value="support">Support</option>
                                    <option value="bpo2">BPO 2</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filter">Availability</label>
                                <select class="form-control">
                                    <option value="0" selected>All</option>
                                    <option value="1">Available</option>
                                    <option value="2">Unavailable</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filter">From Date</label>
                                <div class='input-group date' id='fromdate'>
                                    <input name="from" type='text' class="form-control" />
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="filter">To Date</label>
                                <div class='input-group date' id='todate'>
                                    <input name="to" type='text' class="form-control" />
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"
                                    aria-hidden="true"></span></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<canvas id="latest-assignment-chart" width="800" height="600"></canvas>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.0/Chart.min.js"></script>
<script>
    var full_names = {!! json_encode($full_names) !!};
    var assignment_counts = {!! json_encode($assignment_counts) !!};

    var ctx = document.getElementById("latest-assignment-chart");
    var myChart = new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: full_names,
            datasets: [{
                label: "Total assignment",
                backgroundColor: ["#3e95cd", "#8e5ea2", "#3cba9f", "#e8c3b9", "#c45850"],
                data: assignment_counts
            }]
        },
        options: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Agent by total assignment(s)'
            },
            scales: {
                xAxes: [{
                    ticks: {
                        beginAtZero: true,
                        precision: 0,
                    }
                }]
            }
        }
    });

</script>