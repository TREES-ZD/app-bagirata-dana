<div class="col-md-12">
            <div class="input-group" id="adv-search">
                <input type="text" class="form-control" placeholder="Search for snippets" />
                <div class="input-group-btn">
                    <div class="btn-group" role="group">
                        <div class="dropdown dropdown-lg">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <form class="form-horizontal" role="form">
                                  <div class="form-group">
                                    <label for="filter">Filter by</label>
                                    <select class="form-control">
                                        <option value="0" selected>All Snippets</option>
                                        <option value="1">Featured</option>
                                        <option value="2">Most popular</option>
                                        <option value="3">Top rated</option>
                                        <option value="4">Most commented</option>
                                    </select>
                                  </div>
                                  <div class="form-group">
                                    <label for="contain">Author</label>
                                    <input name="hallo" class="form-control" type="text" />
                                  </div>
                                  <div class="form-group">
                                    <label for="contain">Contains the words</label>
                                    <input class="form-control" type="text" />
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