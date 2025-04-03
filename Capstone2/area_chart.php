<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Chart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts">

    <style>
        #area-chart {
            width: 100%;
            height: 100%; /* Ensures the chart fills the height of its container */
        }
    </style>
</head>
<body>
    <div id="area-chart"></div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        var options = {
            chart: {
                type: 'area',
                width: '60%',
                height: 300,
            },
            series: [{
                name: 'Series 1',
                data: [31, 40, 28, 51, 42, 109, 100]
            }],
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
            }
        }

        var chart = new ApexCharts(document.querySelector("#area-chart"), options);

        chart.render();
    </script>
</body>
</html>