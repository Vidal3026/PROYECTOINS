// Función para dibujar el gráfico de horas de tutoría
function drawAreaChart(labels, data) {
    var ctx = document.getElementById("myAreaChart");
    if (!ctx) return; // Salir si el elemento no existe

    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels, // Eje X: Meses
            datasets: [{
                label: "Horas Impartidas",
                lineTension: 0.3,
                backgroundColor: "rgba(2,117,216,0.2)",
                borderColor: "rgba(2,117,216,1)",
                pointRadius: 5,
                pointBackgroundColor: "rgba(2,117,216,1)",
                pointBorderColor: "rgba(255,255,255,0.8)",
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "rgba(2,117,216,1)",
                pointHitDetectionRadius: 20,
                pointBorderWidth: 2,
                data: data, // Eje Y: Horas
            }],
        },
        options: {
            // Opciones de configuración del gráfico...
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        maxTicksLimit: 5
                    },
                    gridLines: {
                        color: "rgba(0, 0, 0, .125)",
                    }
                }],
            },
            legend: {
                display: false
            }
        }
    });
}

// 1. Obtener datos del endpoint PHP
fetch('obtener_datos_grafico_mensual.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            console.error(data.error);
            return;
        }
        
        // 2. Llamar a la función de dibujo con los datos reales
        drawAreaChart(data.labels, data.data);
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });