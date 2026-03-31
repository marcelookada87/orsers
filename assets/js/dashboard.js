$(function () {
    if (typeof Chart === 'undefined' || typeof chartData === 'undefined') return;

    var labels = ['Aberta', 'Em Andamento', 'Aguardando', 'Finalizada', 'Cancelada'];
    var values = [
        chartData.aberta,
        chartData.em_andamento,
        chartData.aguardando,
        chartData.finalizada,
        chartData.cancelada
    ];
    var colors = ['#3b82f6', '#f97316', '#f59e0b', '#10b981', '#6b7280'];

    var ctx = document.getElementById('chartStatus');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderColor: '#1e293b',
                borderWidth: 3,
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.label + ': ' + ctx.raw;
                        }
                    }
                }
            }
        }
    });

    // Build legend
    var legendHtml = '';
    labels.forEach(function (label, i) {
        legendHtml += '<div class="legend-item">' +
            '<span class="legend-dot" style="background:' + colors[i] + '"></span>' +
            '<span>' + label + ': <strong>' + values[i] + '</strong></span>' +
            '</div>';
    });
    $('#chartLegend').html(legendHtml);
});
