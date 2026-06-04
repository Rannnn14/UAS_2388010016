FROM nginx:alpine

# Salin semua file dari folder web-statis ke direktori HTML default Nginx
COPY . /usr/share/nginx/html/

# Ubah nama Index_CV.html menjadi index.html agar otomatis dibaca oleh Nginx sebagai halaman utama
RUN mv /usr/share/nginx/html/Index_CV.html /usr/share/nginx/html/index.html

# Expose port 80 di dalam container
EXPOSE 80
