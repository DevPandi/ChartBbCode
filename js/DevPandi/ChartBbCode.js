((window, document) => {
    'use strict'
    XF.DevPandi = XF.DevPandi || {};

    class ChartType {
        constructor(chartData, id)
        {
            this.chartData = chartData;
            this.id = this.chartData.id;
            this.type = this.chartData.type;
            this.chartBox = document.getElementById('chart-' + this.id);
            this._readOptions();
            this._readData();
        }

        _readOptions()
        {
            this.options = this._getDefaultOptions();
            if (this.chartData.title) {
                this.options.plugins.title = { display: true, text: this.chartData.title};
            }
        }

        _readData()
        {
            this.data = { labels: this._readXElements(), datasets: this._readYElements() };

        }

        _readYElements()
        {
            let datasets = [];
            let i = 0;
            let readElement = this._readYElement;
            this.chartData.y.forEach(function (element) {
                datasets[i] = readElement(element);
                i++;
            })

            return datasets;
        }

        _readYElement(element)
        {
            let dataset = {};
            dataset.label = element.name;

            if (element.color) {
                dataset.backgroundColor = [element.color];
            }
            if (element.border) {
                dataset.borderColor = [element.border];
            }

            dataset.data = element.data

            return dataset;
        }

        _readXElements()
        {
            if (this.chartData.x) {
                return this.chartData.x;
            } else {
                return [];
            }
        }

        _getDefaultOptions()
        {
            return {
                plugins: { legend: { position: 'bottom' } },
                responsive: true,
                elements: {
                    bar: { borderWidth: 2 }
                }
            }
        }
    }

    class ChartBar extends ChartType {
        _readOptions()
        {
            super._readOptions();

            this.options.scales = {};
            if (this.chartData.useX) {
                this.options.indexAxis = 'y';
                this.options.scales.x = this._readOptionsAxis();
            } else {
                this.options.scales.y = this._readOptionsAxis();
            }
        }

        _readOptionsAxis()
        {
            let axis = {};

            if (this.chartData.min > 0) {
                axis.min = this.chartData.min;
            } else {
                axis.beginAtZero = true;
            }
            if (this.chartData.max) {
                axis.max = this.chartData.max;
            }

            if (!this.chartData.y_axis.tick && this.chartData.y_axis.str) {
                axis.title = { display: true, text: this.chartData.y_axis.str };
            } else {
                axis.ticks = {};
                let text = this.chartData.y_axis.str
                if (this.chartData.y_axis.start) {
                    axis.ticks.callback = function (value, index, ticks) {
                        return value + text;
                    }
                } else {
                    axis.ticks.callback = function (value, index, ticks) {
                        return text + value;
                    }
                }
            }

            return axis;
        }
    }

    class ChartLine extends ChartType {
        _readYElement(element)
        {
            let dataset = super._readYElement(element);

            if (element.dashed) {
                dataset.borderDash = [5,5];
            }

            if (element.point) {
                dataset.pointStyle = element.point.style;
                dataset.pointRadius = element.point.size;
                dataset.pointHoverRadius = element.point.size + 5;
            }

            if (!element.border) {
                dataset.borderColor = [element.color];
            }

            return dataset;
        }

        _readOptions()
        {
            super._readOptions();

            this.options.scales = {};
            this.options.scales.y = this._readOptionsAxis();

            if (this.chartData.x_axis) {
                this.options.scales.x = { title: {display: true, text: this.chartData.x_axis} };
            }
        }

        _readOptionsAxis()
        {
            let axis = {};

            if (this.chartData.min > 0) {
                axis.min = this.chartData.min;
            } else {
                axis.beginAtZero = true;
            }
            if (this.chartData.max) {
                axis.max = this.chartData.max;
            }

            if (!this.chartData.y_axis.tick && this.chartData.y_axis.str) {
                axis.title = { display: true, text: this.chartData.y_axis.str };
            } else {
                axis.ticks = {};
                let text = this.chartData.y_axis.str
                if (this.chartData.y_axis.start) {
                    axis.ticks.callback = function (value, index, ticks) {
                        return value + text;
                    }
                } else {
                    axis.ticks.callback = function (value, index, ticks) {
                        return text + value;
                    }
                }
            }

            return axis;
        }
    }

    class ChartPie extends ChartType {
        _readData()
        {
            this.data = { labels: this.chartData.labels, datasets: [] };
            this.data.datasets[0] = { data: this.chartData.elements.data, backgroundColor: this.chartData.elements.color, borderColor: ['transparent'] }
        }
    }

    class ChartBbCode {
        constructor()
        {
            this.charts = [];
        }

        getChart(chartData)
        {
            switch (chartData.type) {
                case 'bar': return new ChartBar(chartData);
                case 'line': return new ChartLine(chartData);
                case 'pie': return new ChartPie(chartData);
            }
        }

        renderCharts()
        {
            let rawCharts = document.getElementsByClassName('chartBbcodeData');
            for (var i = 0; i < rawCharts.length; i++) {
                this.renderChart(JSON.parse(rawCharts[i].textContent));
            }
        }

        renderChart(rawChart)
        {
            if (this.charts[rawChart.id]) {
                return;
            }
            let chart = this.getChart(rawChart)

            if (!chart) {
                return;
            }
            this.charts[chart.id] = chart;

            new Chart(chart.chartBox, {type: chart.type, data: chart.data, options: chart.options });
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