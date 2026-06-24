<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Python interpreter for CMS-1500 / UB-04 PDF generation
    |--------------------------------------------------------------------------
    | Use forward slashes on Windows, e.g. C:/Users/You/AppData/.../python.exe
    | Requires: pip install -r scripts/requirements.txt
    */
    'python_path' => env('PYTHON_PATH'),
];
