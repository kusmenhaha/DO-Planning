document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('export-xlsx');
    const table = document.getElementById('alokasiTable');

    if (btn && table) {
        btn.addEventListener('click', function () {
            const wb = XLSX.utils.table_to_book(table, { sheet: "Alokasi" });
            XLSX.writeFile(wb, 'alokasi_pengiriman.xlsx');
        });
        console.log('Listener export dipasang');
    } else {
        console.warn("Tombol export-xlsx atau tabel alokasiTable tidak ditemukan");
    }
});
