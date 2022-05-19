# YARM_bookshelf


## Usage (follow/run the following commands in your terminal)

- to install package

composer require yarm/bookshelf

- publish routes/config/views/js/

php artisan vendor:publish --provider="Yarm\Bookshelf\BookshelfServiceProvider" --force

- create the bookshelf table 

php artisan migrate  
 
- connect js to app (copy ... to resources/js/app.js)

require('./vendor/bookshelf')
