let activities = [
  {id:1,date:'2025-11-12',type:'donasi',title:"Jum'at Berkah",amount:300000,status:'verified'},
  {id:2,date:'2025-11-10',type:'penyaluran',title:'Bantuan Sembako',amount:700000,status:'verified'},
  {id:3,date:'2025-11-08',type:'laporan',title:'Bukti pembelian semen',amount:1200000,status:'pending'}
];

function formatRupiah(num){return 'Rp '+num.toLocaleString('id-ID');}

function renderStats(){
  const container = document.getElementById('stats');
  const totalDonasi = activities.filter(a=>a.type==='donasi').reduce((s,a)=>s+a.amount,0);
  const totalPenyaluran = activities.filter(a=>a.type==='penyaluran').reduce((s,a)=>s+a.amount,0);
  const totalLaporan = activities.filter(a=>a.type==='laporan').length;
  const cards = [
    {title:'Total Donasi Masuk',val:formatRupiah(totalDonasi)},
    {title:'Total Penyaluran',val:formatRupiah(totalPenyaluran)},
    {title:'Total Laporan Donatur',val:totalLaporan+' Laporan'}
  ];
  container.innerHTML = '';
  cards.forEach(c=>{
    const el = document.createElement('div'); el.className='card';
    el.innerHTML = `<h4>${c.title}</h4><p class="val">${c.val}</p>`;
    container.appendChild(el);
  });
}

function renderTable(){
  const tbody = document.querySelector('#activityTable tbody');
  tbody.innerHTML='';
  activities.slice().reverse().forEach(row=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${row.date}</td><td>${row.type}</td><td>${row.title}</td><td>${formatRupiah(row.amount)}</td><td>${row.status}</td>`;
    tbody.appendChild(tr);
  });
}

renderStats();
renderTable();
