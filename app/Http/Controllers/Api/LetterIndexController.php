<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class LetterIndexController extends Controller
{
    public function groups()
    {
        // Kembalikan full groups + items
        return response()->json(config('letter_indexes', []));
    }
}
