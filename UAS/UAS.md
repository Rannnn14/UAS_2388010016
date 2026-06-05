# Ujian Akhir Semester : Deploy 2 System Apps Static Web dan Dynamic Web

### 1. Membuat Insteance baru pada AWS Region ap-southeast-1 Singapore

![alt text](image-4.png)

### 2. Membuat Folder Project 
![alt text](image-5.png)

### 3. Memindahkan project web static UTS ke dalam folder project web-statis
![alt text](image-6.png)

### 4. membuat dynamic-app menggunakan php
![alt text](image-7.png)

### 5. Set Up Docker-Hub

Buat repoitory baru pada docker hub
"uas_dinamis_2388010016"
"uas_2388010016"
![alt text](image-16.png)

## Konfigurasi GitHub Repository Secrets
![alt text](image-3.png)

## Siapkan AWS EC2 Instance
A. Konfigurasi AWS Security Group
![alt text](image-8.png)

B. Hubungkan ke EC2 via SSH dan Install Docker
- Update package list

sudo apt-get update -y

- Install Docker

sudo apt-get install docker.io -y

- Aktifkan service Docker

sudo systemctl enable --now docker

- Tambahkan user saat ini ke grup docker agar tidak perlu mengetik 'sudo' di github actions

sudo usermod -aG docker $USER

newgrp docker

- Buat folder aplikasi

mkdir -p ~/app

![alt text](image-10.png)

## Jalankan & Verifikasi 
Commit dan push file

## MENJALANKAN WEB STATIS
![alt text](image-14.png)

# Menjalankan Web Dinamis
![alt text](image-15.png)
