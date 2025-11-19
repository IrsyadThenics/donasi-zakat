let data = [];
let editIndex = -1;

const modal = document.getElementById("modal");
const btnTambah = document.getElementById("btnTambah");
const btnSimpan = document.getElementById("btnSimpan");
const btnBatal = document.getElementById("btnBatal");
const tbody = document.getElementById("tableBody");

btnTambah.onclick = () => {
    editIndex = -1;
    document.getElementById("modalTitle").innerText = "Tambah Laporan";
    modal.style.display = "flex";
};

btnBatal.onclick = () => modal.style.display = "none";

btnSimpan.onclick = () => {
    const obj = {
        judul: document.getElementById("judul").value,
        tanggal: document.getElementById("tanggal").value,
        nominal: document.getElementById("nominal").value,
        keterangan: document.getElementById("keterangan").value
    };

    if (editIndex === -1) {
        data.push(obj);
    } else {
        data[editIndex] = obj;
    }

    tampilkan();
    modal.style.display = "none";
};

function tampilkan() {
    tbody.innerHTML = "";

    data.forEach((item, i) => {
        tbody.innerHTML += `
            <tr>
                <td>${i+1}</td>
                <td>${item.judul}</td>
                <td>${item.tanggal}</td>
                <td>Rp ${item.nominal}</td>
                <td>${item.keterangan}</td>
                <td>
                    <button onclick="edit(${i})">Edit</button>
                    <button onclick="hapus(${i})">Hapus</button>
                </td>
            </tr>
        `;
    });
}

function edit(i) {
    editIndex = i;
    document.getElementById("modalTitle").innerText = "Edit Laporan";

    document.getElementById("judul").value = data[i].judul;
    document.getElementById("tanggal").value = data[i].tanggal;
    document.getElementById("nominal").value = data[i].nominal;
    document.getElementById("keterangan").value = data[i].keterangan;

    modal.style.display = "flex";
}

function hapus(i) {
    data.splice(i, 1);
    tampilkan();
}
