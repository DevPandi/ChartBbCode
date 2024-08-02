((window, document) => {
    'use strict'
    XF.DevPandi = XF.DevPandi || {};

    class ChartBbCode {
        constructor() {
            this.charts = [];
        }

        renderCharts() {
            var rawCharts = document.getElementsByClassName('chartBbcodeData');
            for (var i = 0; i < rawCharts.length; i++) {
                this.renderChart(rawCharts[i]);
            }
        }

        renderChart(rawChart) {
            var chartData = JSON.parse(rawChart.textContent);
            var id = chartData.id;

            if (this.charts[id]) {
                return;
            }

            this.charts[id] = {data: {}, options: {}, type: chartData.type};
            this.charts[id].options = this.readOptions(chartData, this.charts[id].type);
            this.charts[id].data = this.readData(chartData, this.charts[id].type);


            var chartBox = document.getElementById('chart-' + id);
            new Chart(chartBox, {type: this.charts[id].type, data: this.charts[id].data, options: this.charts[id].options });
        }

        readOptions(chartData, type) {
            var options = {}
            options.plugins = {};
            options.plugins.legend = {};
            options.plugins.legend.position = 'bottom';
            options.responsive = true;
            options.elements = {};
            options.elements.bar = {};
            options.elements.bar.borderWidth = 2

            options.scales = {};
            if (chartData.useX) {
                options.indexAxis = 'y';
                options.scales.x = this.readOptionsAxis(chartData);
            } else {
                options.scales.y = this.readOptionsAxis(chartData);
                if (type == 'line' && chartData.x_axis) {
                    options.scales.x = { };
                    options.scales.x.display = true;
                    options.scales.x.title = { display: true, text: chartData.x_axis };
                }
            }

            if (chartData.title) {
                options.plugins.title = {};
                options.plugins.title.display = true;
                options.plugins.title.text = chartData.title;
            }

            return options;
        }

        readOptionsAxis(chartData, type) {
            var axis = {};
            if (chartData.min > 0) {
                axis.min = chartData.min;
            } else {
                axis.beginAtZero = true;
            }
            if (chartData.end) {
                axis.max = chartData.max;
            }

            if (!chartData.y_axis.tick && chartData.y_axis.str) {
                axis.title = { display: true, text: chartData.y_axis.str };
            } else {
                axis.ticks = {};
                if (chartData.y_axis.start) {
                    axis.ticks.callback = function (value, index, ticks) {
                        return value + chartData.y_axis.str;
                    }
                }
                else {
                    axis.ticks.callback = function (value, index, ticks) {
                        return chartData.y_axis.str + value;
                    }
                }
            }

            return axis;
        }

        readData(chartData, type) {
            var data = { labels: [], datasets: [] };
            var i = 0;

            // labes for x or y axis
            if (chartData.x) {
                data.labels = chartData.x;
            }

            chartData.y.forEach(function (element) {
                var dataset = {};
                dataset.label = element.name;
                if (element.color) {
                    dataset.backgroundColor = [element.color];
                }
                if (element.border) {
                    dataset.borderColor = [element.border];
                }

                if (type == 'line' && element.dashed) {
                    dataset.borderDash = [5,5];
                }

                if (type == 'line' && element.point) {
                    dataset.pointStyle = element.point.style,
                    dataset.pointRadius = element.point.size;
                    dataset.pointHoverRadius = element.point.size + 5;
                }
                dataset.data = element.data

                data.datasets[i] = dataset;
                i++;
            })

            return data;
        }
    }

    XF.DevPandi.ChartBbCode = new ChartBbCode();
    XF.DevPandi.ChartBbCode.renderCharts();

    XF.Element.extend("quick-reply", {
        __backup: {
            "afterSubmit": "_afterSubmitExtension"
        },

        afterSubmit: function(e, data)
        {
            this._afterSubmitExtension(e, data);

            XF.DevPandi.ChartBbCode.renderCharts();
        }
    })

    XF.Element.extend('quick-edit', {
        __backup: {
            "editSubmit": "_editSubmit"
        },

        editSubmit: function(e)
        {
            this._editSubmit(e);

            XF.DevPandi.ChartBbCode.renderCharts();
        }
    })

    XF.Element.extend('message-loader', {
        __backup: {
            "loaded": "_loaded"
        },

        loaded: function(data)
        {
            this._loaded(data);

            XF.DevPandi.ChartBbCode.renderCharts();
        }
    })
})(window, document)