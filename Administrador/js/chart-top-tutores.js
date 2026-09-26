document.addEventListener('DOMContentLoaded', function() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    fetch(`api_data_top_tutores.php?fi=${fechaInicio}&ff=${fechaFin}`)
        .then(response => response.json())
        .then(data => {
            var ctx = document.getElementById("chartTopTutores");
            if (!ctx) return; 

            new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: "Comisión Generada ($)",
                        backgroundColor: "rgba(40,167,69,0.8)", // Verde (éxito)
                        data: data.data,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return '$' + parseFloat(value).toFixed(2); } } }],
                    },
                    legend: { display: false }
                }
            });
        })
        .catch(error => console.error('Error al cargar Top Tutores:', error));
});