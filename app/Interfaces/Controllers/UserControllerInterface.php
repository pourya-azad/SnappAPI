<?php

namespace App\Interfaces\Controllers;

use Illuminate\Http\Request;

interface UserControllerInterface
{
    public function status(Request $request);
}
