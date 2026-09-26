// Configuración global de Chart.js
Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#292b2c';

function setupBarChartAdmin() {
    // Llama al endpoint de Ingresos
    fetch('api_data_ingresos.php') 
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error al cargar datos del gráfico de barras:', data.error);
                return;
            }

            var ctx = document.getElementById("myBarChart");
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: "Ingresos $",
                        backgroundColor: "rgba(2,117,216,1)",
                        borderColor: "rgba(2,117,216,1)",
                        data: data.data,
                    }],
                },
                options: {
                    scales: {
                        xAxes: [{
                            gridLines: { display: false },
                            ticks: { maxTicksLimit: 6 }
                        }],
                        yAxes: [{
                            ticks: {
                                min: 0,
                                maxTicksLimit: 5,
                                callback: function(value, index, values) {
                                    return '$' + value.toLocaleString('es-ES'); // Formato de moneda
                                }
                            },
                            gridLines: { display: true }
                        }],
                    },
                    legend: { display: false }
                }
            });
        })
        .catch(error => {
            console.error('Error de red al obtener datos de ingresos:', error);
        });
}

document.addEventListener('DOMContentLoaded', setupBarChartAdmin);