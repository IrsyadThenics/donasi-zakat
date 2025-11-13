-- ========================================
-- SISTEM MANAJEMEN DONASI - Oracle 11g
-- ========================================
-- Script untuk membuat semua tabel sesuai database schema

-- Drop existing sequences and tables (jika diperlukan)
-- DROP SEQUENCE seq_penerima;
-- DROP SEQUENCE seq_donatur;
-- DROP SEQUENCE seq_campaign;
-- DROP SEQUENCE seq_donasi;
-- DROP SEQUENCE seq_pembayaran;
-- DROP SEQUENCE seq_laporan;

-- ========================================
-- 1. CREATE SEQUENCES (untuk auto-increment)
-- ========================================
CREATE SEQUENCE seq_penerima START WITH 1 INCREMENT BY 1 NOCYCLE;
CREATE SEQUENCE seq_donatur START WITH 1 INCREMENT BY 1 NOCYCLE;
CREATE SEQUENCE seq_campaign START WITH 1 INCREMENT BY 1 NOCYCLE;
CREATE SEQUENCE seq_donasi START WITH 1 INCREMENT BY 1 NOCYCLE;
CREATE SEQUENCE seq_pembayaran START WITH 1 INCREMENT BY 1 NOCYCLE;
CREATE SEQUENCE seq_laporan START WITH 1 INCREMENT BY 1 NOCYCLE;

-- ========================================
-- 2. CREATE TABLE PENERIMA (Recipient/Beneficiary)
-- ========================================
CREATE TABLE penerima (
    id_penerima NUMBER PRIMARY KEY,
    username VARCHAR2(50) NOT NULL UNIQUE,
    email VARCHAR2(100) NOT NULL UNIQUE,
    password_hash CHAR(64) NOT NULL,
    tanggal_daftar DATE NOT NULL DEFAULT SYSDATE,
    status VARCHAR2(20) NOT NULL CHECK (status IN ('Aktif', 'Nonaktif')) DEFAULT 'Aktif',
    created_at DATE NOT NULL DEFAULT SYSDATE,
    updated_at DATE NOT NULL DEFAULT SYSDATE
);

-- Index untuk Penerima
CREATE INDEX idx_penerima_username ON penerima(username);
CREATE INDEX idx_penerima_email ON penerima(email);

-- ========================================
-- 3. CREATE TABLE DONATUR (Donor)
-- ========================================
CREATE TABLE donatur (
    id_donatur NUMBER PRIMARY KEY,
    nama_donatur VARCHAR2(100) NOT NULL,
    email VARCHAR2(100) NOT NULL UNIQUE,
    nomor_telepon VARCHAR2(15),
    tanggal_terdaftar DATE NOT NULL DEFAULT SYSDATE,
    created_at DATE NOT NULL DEFAULT SYSDATE,
    updated_at DATE NOT NULL DEFAULT SYSDATE
);

-- Index untuk Donatur
CREATE INDEX idx_donatur_email ON donatur(email);
CREATE INDEX idx_donatur_nama ON donatur(nama_donatur);

-- ========================================
-- 4. CREATE TABLE CAMPAIGN (Kampanye Donasi)
-- ========================================
CREATE TABLE campaign (
    id_campaign NUMBER PRIMARY KEY,
    id_penerima NUMBER NOT NULL,
    judul_campaign VARCHAR2(255) NOT NULL,
    deskripsi CLOB NOT NULL,
    target_dana DECIMAL(12, 2) NOT NULL,
    dana_terkumpul DECIMAL(12, 2) NOT NULL DEFAULT 0,
    tanggal_mulai DATE NOT NULL,
    tanggal_deadline DATE NOT NULL,
    status VARCHAR2(20) NOT NULL CHECK (status IN ('Aktif', 'Selesai', 'Ditangguhkan')) DEFAULT 'Aktif',
    created_at DATE NOT NULL DEFAULT SYSDATE,
    updated_at DATE NOT NULL DEFAULT SYSDATE,
    CONSTRAINT fk_campaign_penerima FOREIGN KEY (id_penerima) REFERENCES penerima(id_penerima) ON DELETE CASCADE
);

-- Index untuk Campaign
CREATE INDEX idx_campaign_penerima ON campaign(id_penerima);
CREATE INDEX idx_campaign_status ON campaign(status);
CREATE INDEX idx_campaign_tanggal ON campaign(tanggal_mulai, tanggal_deadline);

-- ========================================
-- 5. CREATE TABLE DONASI (Donation)
-- ========================================
CREATE TABLE donasi (
    id_donasi NUMBER PRIMARY KEY,
    id_donatur NUMBER NOT NULL,
    id_campaign NUMBER NOT NULL,
    jumlah_donasi DECIMAL(10, 2) NOT NULL,
    pesan_donatur TEXT,
    is_anonim NUMBER(1) DEFAULT 0,
    tanggal_donasi DATE NOT NULL DEFAULT SYSDATE,
    created_at DATE NOT NULL DEFAULT SYSDATE,
    updated_at DATE NOT NULL DEFAULT SYSDATE,
    CONSTRAINT fk_donasi_donatur FOREIGN KEY (id_donatur) REFERENCES donatur(id_donatur) ON DELETE CASCADE,
    CONSTRAINT fk_donasi_campaign FOREIGN KEY (id_campaign) REFERENCES campaign(id_campaign) ON DELETE CASCADE
);

-- Index untuk Donasi
CREATE INDEX idx_donasi_donatur ON donasi(id_donatur);
CREATE INDEX idx_donasi_campaign ON donasi(id_campaign);
CREATE INDEX idx_donasi_tanggal ON donasi(tanggal_donasi);

-- ========================================
-- 6. CREATE TABLE PEMBAYARAN (Payment)
-- ========================================
CREATE TABLE pembayaran (
    id_pembayaran NUMBER PRIMARY KEY,
    id_donasi NUMBER NOT NULL,
    kode_transaksi VARCHAR2(50) NOT NULL UNIQUE,
    metode_pembayaran VARCHAR2(50) NOT NULL,
    status_bayar VARCHAR2(20) NOT NULL CHECK (status_bayar IN ('Pending', 'Sukses', 'Gagal')) DEFAULT 'Pending',
    tanggal_pembayaran DATE NOT NULL DEFAULT SYSDATE,
    biaya_admin DECIMAL(5, 2) DEFAULT 0,
    created_at DATE NOT NULL DEFAULT SYSDATE,
    updated_at DATE NOT NULL DEFAULT SYSDATE,
    CONSTRAINT fk_pembayaran_donasi FOREIGN KEY (id_donasi) REFERENCES donasi(id_donasi) ON DELETE CASCADE
);

-- Index untuk Pembayaran
CREATE INDEX idx_pembayaran_donasi ON pembayaran(id_donasi);
CREATE INDEX idx_pembayaran_kode ON pembayaran(kode_transaksi);
CREATE INDEX idx_pembayaran_status ON pembayaran(status_bayar);

-- ========================================
-- 7. CREATE TABLE LAPORAN (Report)
-- ========================================
CREATE TABLE laporan (
    id_laporan NUMBER PRIMARY KEY,
    id_campaign NUMBER NOT NULL,
    id_penerima NUMBER NOT NULL,
    judul_laporan VARCHAR2(255) NOT NULL,
    isi_laporan CLOB NOT NULL,
    tanggal_generate DATE NOT NULL DEFAULT SYSDATE,
    total_dana_terkumpul DECIMAL(12, 2),
    created_at DATE NOT NULL DEFAULT SYSDATE,
    updated_at DATE NOT NULL DEFAULT SYSDATE,
    CONSTRAINT fk_laporan_campaign FOREIGN KEY (id_campaign) REFERENCES campaign(id_campaign) ON DELETE CASCADE,
    CONSTRAINT fk_laporan_penerima FOREIGN KEY (id_penerima) REFERENCES penerima(id_penerima) ON DELETE CASCADE
);

-- Index untuk Laporan
CREATE INDEX idx_laporan_campaign ON laporan(id_campaign);
CREATE INDEX idx_laporan_penerima ON laporan(id_penerima);
CREATE INDEX idx_laporan_tanggal ON laporan(tanggal_generate);

-- ========================================
-- 8. CREATE TRIGGERS untuk Update Timestamp
-- ========================================

-- Trigger untuk update Penerima
CREATE OR REPLACE TRIGGER trg_penerima_update
BEFORE UPDATE ON penerima
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSDATE;
END;
/

-- Trigger untuk update Donatur
CREATE OR REPLACE TRIGGER trg_donatur_update
BEFORE UPDATE ON donatur
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSDATE;
END;
/

-- Trigger untuk update Campaign
CREATE OR REPLACE TRIGGER trg_campaign_update
BEFORE UPDATE ON campaign
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSDATE;
END;
/

-- Trigger untuk update Donasi
CREATE OR REPLACE TRIGGER trg_donasi_update
BEFORE UPDATE ON donasi
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSDATE;
END;
/

-- Trigger untuk update Pembayaran
CREATE OR REPLACE TRIGGER trg_pembayaran_update
BEFORE UPDATE ON pembayaran
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSDATE;
END;
/

-- Trigger untuk update Laporan
CREATE OR REPLACE TRIGGER trg_laporan_update
BEFORE UPDATE ON laporan
FOR EACH ROW
BEGIN
    :NEW.updated_at := SYSDATE;
END;
/

-- ========================================
-- 9. CREATE VIEWS untuk Analytics
-- ========================================

-- View: Ringkasan Campaign
CREATE OR REPLACE VIEW vw_campaign_summary AS
SELECT 
    c.id_campaign,
    c.judul_campaign,
    p.username AS penerima_username,
    c.target_dana,
    c.dana_terkumpul,
    ROUND((c.dana_terkumpul / c.target_dana) * 100, 2) AS persentase_target,
    c.status,
    c.tanggal_mulai,
    c.tanggal_deadline,
    TRUNC(c.tanggal_deadline - SYSDATE) AS hari_tersisa,
    COUNT(d.id_donasi) AS jumlah_donatur
FROM campaign c
LEFT JOIN penerima p ON c.id_penerima = p.id_penerima
LEFT JOIN donasi d ON c.id_campaign = d.id_campaign
GROUP BY c.id_campaign, c.judul_campaign, p.username, c.target_dana, 
         c.dana_terkumpul, c.status, c.tanggal_mulai, c.tanggal_deadline;

-- View: Detail Donasi Per Campaign
CREATE OR REPLACE VIEW vw_donasi_detail AS
SELECT 
    c.id_campaign,
    c.judul_campaign,
    d.id_donasi,
    DECODE(d.is_anonim, 1, 'Anonymous', dr.nama_donatur) AS nama_donatur,
    dr.email AS email_donatur,
    d.jumlah_donasi,
    d.pesan_donatur,
    pb.status_bayar,
    d.tanggal_donasi
FROM donasi d
LEFT JOIN donatur dr ON d.id_donatur = dr.id_donatur
LEFT JOIN campaign c ON d.id_campaign = c.id_campaign
LEFT JOIN pembayaran pb ON d.id_donasi = pb.id_donasi;

-- ========================================
-- Commit all changes
-- ========================================
COMMIT;

-- Display summary
SELECT table_name FROM user_tables WHERE table_name IN ('PENERIMA', 'DONATUR', 'CAMPAIGN', 'DONASI', 'PEMBAYARAN', 'LAPORAN');
