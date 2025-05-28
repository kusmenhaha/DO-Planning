const API_URL = 'assets/backend/hitungalokasi.php';

async function fetchAllocationData() {
  try {
    const res = await fetch(API_URL);
    if (!res.ok) throw new Error(`HTTP error ${res.status}`);
    const json = await res.json();

    if (json.status !== 'success') {
      alert('Error: ' + (json.message || 'Gagal mengambil data'));
      return [];
    }

    return json.data;
  } catch (error) {
    console.error('Fetch error:', error);
    alert('Gagal mengambil data.');
    return [];
  }
}

function renderGrid(data) {
  new gridjs.Grid({
    columns: [
      { id: 'CUST#', name: 'Customer' },
      { id: 'SKU', name: 'SKU' },
      { id: 'CHANNEL', name: 'Channel' },
      { id: 'OTD_CMO', name: 'OTD CMO', formatter: (cell) => gridjs.html(`<b>${cell}</b>`) },
      { id: 'SISA_STOCK', name: 'Sisa Stock' },
      { id: 'ALLOCATED_QTY', name: 'Allocated Qty' },
      { id: 'USED_VOLUME', name: 'Used Volume' },
      { id: 'MIXCUST', name: 'Mix Cust' }
    ],
    data: data,
    pagination: {
      enabled: true,
      limit: 10,
    },
    search: true,
    sort: true,
    fixedHeader: true,
    height: '450px'
  }).render(document.getElementById('grid-wrapper'));
}

(async () => {
  const data = await fetchAllocationData();
  renderGrid(data);
})();
