// Admin/js/chart-pie-demo.js
Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#292b2c';

document.addEventListener('DOMContentLoaded', function() {
    // Usamos el nuevo endpoint
    fetch('api_data_usuarios_pie.php') 
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error del servidor:', data.error);
                return;
            }

            var ctx = document.getElementById("myPieChart");
            if (!ctx) return; 

            new Chart(ctx, {
                type: 'doughnut', // Tipo de pastel
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: ['#007bff', '#17a2b8', '#dc3545', '#ffc107'], // Colores para los segmentos (azul primario y cian info)
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        // Muestra el porcentaje en el tooltip
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var total = dataset.data.reduce((acc, val) => acc + val, 0);
                                var currentValue = dataset.data[tooltipItem.index];
                                var percentage = Math.round((currentValue / total) * 100);
                                return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' + percentage + '%)';
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        display: true
                    },
                }
            });
        })
        .catch(error => {
            console.error('Error al cargar datos del gráfico de pastel:', error);
        });
});