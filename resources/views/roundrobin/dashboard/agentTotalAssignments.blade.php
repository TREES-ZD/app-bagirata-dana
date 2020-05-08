<div class="col-md-12">
            <div class="input-group" id="adv-search">
                <input type="text" class="form-control" placeholder="Filter" />
                <div class="input-group-btn">
                    <div class="btn-group" role="group">
                        <div class="dropdown dropdown-lg">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></button>
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
                                  <!-- <div class="form-group">
                                    <label for="contain">From</label>
                                    <input name="from" class="form-control" type="date" />
                                  </div>
                                  <div class="form-group">
                                    <label for="contain">To</label>
                                    <input name="to" class="form-control" type="date" />
                                  </div> -->
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
                                  <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>

<canvas id="bar-chart-horizontal" width="800" height="600"></canvas>
<script type="text/javascript">
            $(function () {
                $('#fromdate').datetimepicker();
                $('#todate').datetimepicker();
            });
        </script>

<script>
    

$(function () {
    // var ctx = document.getElementById("myChart").getContext('2d');
    var full_names = {!! json_encode($full_names) !!};
    var assignment_counts = {!! json_encode($assignment_counts) !!};
    // console.log(full_names, assignment_counts)
    new Chart(document.getElementById("bar-chart-horizontal"), {
        type: 'horizontalBar',
        data: {
        labels: full_names,
        datasets: [
            {
            label: "Total assignment",
            backgroundColor: ["#3e95cd", "#8e5ea2","#3cba9f","#e8c3b9","#c45850"],
            data: assignment_counts
            }
        ]
        },
        options: {
        legend: { display: false },
        title: {
            display: true,
            text: 'Agent by total assignment(s)'
        }
        }
    });
});

</script>