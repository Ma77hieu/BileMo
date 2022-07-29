# BileMo API

### 1.Clone the repository
run `https://github.com/Ma77hieu/BileMo.git`

### 2.Install required libraries
run `composer install`

### 3.Configure your database inside the .env file
place the correct values in the DATABASE_URL constant of the .env file

### 4.Create your database
run `php bin/console doctrine:database:create`

### 5.Apply the project migrations
in order to create the database tables, run
`php bin/console doctrine:migrations:migrate`