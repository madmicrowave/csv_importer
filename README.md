### S3 bucket *.csv file importer

---

Connect any amount of buckets and read any type of csv files to standardised data structures.
Script will automatically build tables based on file name, same file name structure files will be loaded in same table.

<br />
<br />

For development environment use command prefix `APP_ENV=dev` to use `.env.dev` config file 

##### Command Samples 

* Add bucket: `php artisan bucket:add`
* Run bucket/s *.csv files import: `php artisan bucket:import ${BUCKET_NAME:-all}`

&nbsp;
&nbsp;

### Setup development environment

---

##### Requirements:

* [Docker](https://docs.docker.com/install/linux/docker-ce/ubuntu/) >= 19.03.x
* [Docker-Compose](https://docs.docker.com/compose/install/) >= 1.22.x

##### Commands:

1. Launch development containers: `docker-composer up --build`
2. Initialize schema: 
    1. Exec ubuntu container: `docker exec -it csv_importer_ubuntu_1 /bin/bash`
    2. Run schema creation: `php artisan db:create`
    3. Run migrations: `php artisan migrate`
    
&nbsp;
&nbsp;


### TODO:

---

* Docker: move `microsoft/mssql-server-linux:latest` under same ubuntu container
* Docker: replace `CMD sleep 9999` to infine deamon worker

&nbsp;
&nbsp;    
    
**Developed by [Maksims Gerasimovs](https://www.facebook.com/maksims.gerasimovs)** 
