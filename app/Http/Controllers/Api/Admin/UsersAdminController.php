<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsersAdminController extends Controller
{
    public function index(Request $request)
    {
        // You can later enforce role/permissions here.
        return User::query()
            ->where('tenant_id', app('tenant')->id ?? $request->user()->tenant_id)
            ->orderBy('id', 'desc')
            ->paginate(20);
    }
}
