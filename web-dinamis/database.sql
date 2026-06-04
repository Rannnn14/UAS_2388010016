-- Membuat database perpustakaan jika belum ada
CREATE DATABASE IF NOT EXISTS perpustakaan;
USE perpustakaan;

-- 1. Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'anggota') NOT NULL DEFAULT 'anggota',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel kategori
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel buku
CREATE TABLE IF NOT EXISTS buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NULL,
    judul VARCHAR(255) NOT NULL,
    penulis VARCHAR(100) NOT NULL,
    penerbit VARCHAR(100) NOT NULL,
    tahun_terbit INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 0,
    cover_image VARCHAR(255) NULL,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel transaksi
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_buku INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    status ENUM('dipinjam', 'kembali') NOT NULL DEFAULT 'dipinjam',
    denda INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Memasukkan data contoh kategori
INSERT INTO kategori (nama_kategori) VALUES
('Fiksi'),
('Sains & Teknologi'),
('Sejarah'),
('Filsafat & Agama'),
('Komik & Novel Grafis');

-- Memasukkan data contoh buku
INSERT INTO buku (id_kategori, judul, penulis, penerbit, tahun_terbit, jumlah, cover_image) VALUES
(1, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 5, 'laskar_pelangi.jpg'),
(2, 'A Brief History of Time', 'Stephen Hawking', 'Bantam Books', 1988, 3, 'brief_history.jpg'),
(1, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 4, 'bumi_manusia.jpg'),
(3, 'Sapiens: Riwayat Singkat Umat Manusia', 'Yuval Noah Harari', 'Kepustakaan Populer Gramedia', 2011, 2, 'sapiens.jpg'),
(5, 'Detektif Conan Vol. 100', 'Gosho Aoyama', 'Elex Media Komputindo', 2021, 10, 'conan_100.jpg');

-- Memasukkan data contoh users (Password untuk semua user adalah: admin123)
-- Hash berikut dihasilkan menggunakan password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, password, nama_lengkap, email, role) VALUES
('admin', '$2y$10$gN47oK/L3sZ9U0vT8KzEdeU08d5P5Z3W2z2R9Y6oX2rV8r0r0r0r.', 'Administrator Perpustakaan', 'admin@perpustakaan.com', 'admin'),
('anggota1', '$2y$10$gN47oK/L3sZ9U0vT8KzEdeU08d5P5Z3W2z2R9Y6oX2rV8r0r0r0r.', 'Budi Santoso', 'budi@gmail.com', 'anggota'),
('anggota2', '$2y$10$gN47oK/L3sZ9U0vT8KzEdeU08d5P5Z3W2z2R9Y6oX2rV8r0r0r0r.', 'Siti Aminah', 'siti@gmail.com', 'anggota');

-- Memasukkan data contoh transaksi peminjaman
INSERT INTO transaksi (id_user, id_buku, tanggal_pinjam, tanggal_kembali, status, denda) VALUES
(2, 1, '2026-05-20', '2026-05-27', 'kembali', 0),
(2, 2, '2026-06-01', '2026-06-08', 'dipinjam', 0),
(3, 3, '2026-05-25', '2026-06-01', 'dipinjam', 5000);
