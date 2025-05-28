document.addEventListener("DOMContentLoaded", function () {
    fetch('assets/backend/get_allocationsetting.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.settings) {
                const {
                    minCarton = '',
                    shipmentSizePercent = '',
                    rangeFullCMO = '',
                    SkuLimit = '',
                    MaxCMO = ''
                } = data.settings;

                document.getElementById('minCarton').value = minCarton;
                document.getElementById('shipmentSizePercent').value = shipmentSizePercent;
                document.getElementById('rangeFullCMO').value = rangeFullCMO;
                document.getElementById('SkuLimit').value = SkuLimit;
                document.getElementById('MaxCMO').value = MaxCMO;
            }
        });
});

function applySettings() {
    const minCarton = parseInt(document.getElementById('minCarton').value) || 0;
    const shipmentSizePercent = parseFloat(document.getElementById('shipmentSizePercent').value) || 0;
    const rangeFullCMO = document.getElementById('rangeFullCMO').value;
    const SkuLimit = parseInt(document.getElementById('SkuLimit').value) || 0;
    const MaxCMO = parseFloat(document.getElementById('MaxCMO').value) / 100 || 0;

    fetch('assets/backend/allocationsetting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            minCarton,
            shipmentSizePercent,
            rangeFullCMO,
            SkuLimit,
            MaxCMO
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Pengaturan berhasil diterapkan!");
        } else {
            alert("Gagal menyimpan pengaturan: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Terjadi kesalahan saat menyimpan pengaturan.");
    });
}

function resetSettings() {
    fetch('assets/backend/reset_allocationsetting.php', {
        method: 'POST', // Biasanya reset menggunakan POST atau DELETE, tapi GET juga bisa
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Setelah sesi direset di backend, kosongkan nilai di form frontend
            document.getElementById('minCarton').value = '';
            document.getElementById('shipmentSizePercent').value = '';
            document.getElementById('rangeFullCMO').value = '';
            document.getElementById('SkuLimit').value = '';
            document.getElementById('MaxCMO').value = ''; // Reset juga kolom baru

            alert("Pengaturan telah direset!");
            console.log("Settings have been reset in session and frontend.");
        } else {
            alert("Gagal mereset pengaturan: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error resetting settings:", error);
        alert("Terjadi kesalahan saat mereset pengaturan.");
    });
}