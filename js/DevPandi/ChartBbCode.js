((window, document) => {


    var charts = document.getElementsByName('chartBbcodeData');
    for (i = 0; i < charts.length; i++) {
        var chart = JSON.parse(charts[i].textContent);
        console.log(chart);
        // create chart data
        var chartData = {};
        if (chart.x) {
            chartData.labels = chart.x;
        }
        var datasets = [];
        for (j = 0; j < chart.y.elements.length; j++) {
            var element =  chart.y.elements[j];
            var dataset = {};
            dataset.label = element.name;
            if (element.color) {
                dataset.backgroundColor = [element.color];
            }
            if (element.border) {
                dataset.borderColor = [element.border];
            }

            dataset.data = element.data;

            datasets[j] = dataset;
        }
        chartData.datasets = datasets;

        // create options
        var chartOptions = {};
        if (chart.useX) {
            chartOptions.indexAxis = 'y';
        }
        chartOptions.plugins = {};
        chartOptions.plugins.legend = {};
        chartOptions.plugins.legend.position = 'bottom';

        if (chart.title) {
            chartOptions.plugins.title = {};
            chartOptions.plugins.title.display = true;
            chartOptions.plugins.title.text = chart.title;
        }

        chartOptions.responsive = true;
        chartOptions.elements = {};
        chartOptions.elements.bar = {};
        chartOptions.elements.bar.borderWidth = 2

        var axis = {};
        if (chart.startAt > 0) {
            axis.min = chart.startAt;
        } else {
            axis.beginAtZero = true;
        }

        if (chart.endAt) {
            axis.max = chartOptions.endAt;
        }

        if (chart.y.tick == false && chart.y.str) {
            axis.title = {}
            axis.title.display = true;
            axis.title.text = chart.y.str;
        } else {
            axis.ticks = {};
            if (chart.y.start) {
                axis.ticks.callback = function(value, index, ticks) { return value + chart.y.str; };
            } else {
                axis.ticks.callback = function(value, index, ticks) { return  chart.y.str + value; };
            }
        }

        chartOptions.scales = {};
        if (chart.useX) {
            chartOptions.scales.x = axis;
        } else {
            chartOptions.scales.y = axis;
        }

        chartBox = document.getElementById('chart-' + chart.id);
        new Chart(chartBox, {type: 'bar', data: chartData, options: chartOptions});
    }

})(window, document)