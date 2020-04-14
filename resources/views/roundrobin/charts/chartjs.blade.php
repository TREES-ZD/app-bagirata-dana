<canvas id="myChart" width="400" height="400"></canvas>
<script>

$(function () {
    var ctx = document.getElementById("myChart").getContext('2d');
    var full_names = {!! json_encode($full_names) !!};
    var assignment_counts = {!! json_encode($assignment_counts) !!};
    console.log(full_names, assignment_counts)
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: full_names,
            datasets: [{
                label: 'Tickets assigned',
                data: assignment_counts,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        stepSize: 1,
                        beginAtZero:true
                    }
                }]
            }
        }
    });
});
</script>