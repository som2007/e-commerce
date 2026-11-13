<?php

return [
    'name' => 'Threadwell',

    'env' => getenv('APP_ENV') ?: 'local',

    'url' => getenv('APP_URL')
        ?: 'http://localhost/threadwell/public',
];