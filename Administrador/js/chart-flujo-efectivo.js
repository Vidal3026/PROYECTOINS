document.addEventListener('DOMContentLoaded', function() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    
    // Llamada al endpoint con las fechas filtradas
    fetch(`api_data_flujo_efectivo.php?fi=${fechaInicio}&ff=${fechaFin}`)
        .then(response => response.json())
        .then(data => {
            var ctx = document.getElementById("chartFlujoEfectivo");
            if (!ctx) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: "Ingreso Bruto",
                            borderColor: "rgba(2,117,216,1)",
                            backgroundColor: "rgba(2,117,216,0.2)",
                            data: data.ingreso_bruto,
                        },
                        {
                            label: "Ganancia (Comisión)",
                            borderColor: "rgba(255,193,7,1)",
                            backgroundColor: "rgba(255,193,7,0.2)",
                            data: data.comision_neta,
                        }
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                callback: function(value) { return '$' + parseFloat(value).toFixed(2); }
                            }
                        }]
                    }
                }
            });
        })
        .catch(error => console.error('Error al cargar Flujo de Efectivo:', error));
});