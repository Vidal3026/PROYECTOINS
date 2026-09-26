// Función para dibujar el gráfico de barras
function drawBarChart(labels, data) {
    var ctx = document.getElementById("myBarChart");
    if (!ctx) return; // Salir si el elemento no existe

    var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels, // Eje X: Nombres de Materias
            datasets: [{
                label: "Tutorías",
                backgroundColor: "rgba(40,167,69,1)", // Color verde para las barras
                borderColor: "rgba(40,167,69,1)",
                data: data, // Eje Y: Conteo de Tutorías
            }],
        },
        options: {
            scales: {
                xAxes: [{
                    time: {
                        unit: 'month'
                    },
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 6
                    }
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        // Asegura que los ticks sean números enteros ya que son conteos
                        callback: function(value) { if (value % 1 === 0) { return value; } }
                    },
                    gridLines: {
                        display: true
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
fetch('obtener_datos_grafico_materia.php')
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
        drawBarChart(data.labels, data.data);
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });