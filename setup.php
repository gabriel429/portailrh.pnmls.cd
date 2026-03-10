<?php
system('php artisan key:generate');
system('php artisan migrate --force');
system('php artisan db:seed --force');
system('php artisan storage:link');
system('php artisan cache:clear');
system('php artisan config:clear');
echo "✅ Setup terminé!";
?>