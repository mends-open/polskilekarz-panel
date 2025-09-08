<?php

return [
    'endpoint' => env('EMA_ENDPOINT', 'https://www.ema.europa.eu/en/documents/other/article-57-product-data_en.xlsx'),
    'storage_dir' => env('EMA_STORAGE_DIR', 'ema'),
    'chunk_size' => env('EMA_CHUNK_SIZE', 500),
];

