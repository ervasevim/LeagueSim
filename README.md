docker exec -it leaguesim-fpm bash

php artisan migrate
php artisan db:seed

npm run dev


# LeagueSimulator Projesi - Kurulum
Bu belge, LeagueSim projesini yerel ortamda Docker kullanarak çalıştırmak ve geliştirmek için gerekli adımları açıklamaktadır.

---


## Başlangıç

### 1. Docker Container’a Bağlanma

Projede PHP işlemlerini gerçekleştireceğiniz container'a bağlanmak için:

```bash
docker-compose up -d --build

docker exec -it leaguesim-fpm bash
```

### 2. Veritabanı Migration ve Seed
Container içinde veritabanı tablolarını oluşturmak ve başlangıç verilerini eklemek için sırasıyla şu komutları çalıştırın:

```bash
php artisan migrate
php artisan db:seed
```

### 3. Frontend Bağımlılıkları ve Derleme
   Proje kök dizininde (container dışında) aşağıdaki komutları çalıştırarak frontend bağımlılıklarını yükleyin ve geliştirme modunda derleyin:

```bash
npm install
npm run dev

```
