<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()->orderBy('id','desc');

        // Optional filtering (used by enrollment UI)
        if ($role = $request->query('role')) {
            if (in_array($role, ['admin','trainer','learner'], true)) {
                $q->where('role', $role);
            }
        }
        if ($s = $request->query('search')) {
            $q->where(function($qq) use ($s){
                $qq->where('name','like',"%$s%")
                   ->orWhere('email','like',"%$s%")
                   ->orWhere('phone','like',"%$s%");
            });
        }
        return response()->json($q->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'email'=>'nullable|email|max:190|unique:users,email',
            'phone'=>'nullable|string|max:30|unique:users,phone',
            'role'=>'required|in:admin,trainer,learner',
            'language'=>'required|in:en,ms,ta,zh',
            'status'=>'required|in:active,inactive',
            'password'=>'required|string|min:8'
        ]);
        if (!$data['email'] && !$data['phone']) {
            return response()->json(['message'=>'Email or phone required'], 422);
        }
        $data['password'] = Hash::make($data['password']);
        $u = User::create($data);
        return response()->json(['user'=>$u], 201);
    }

    public function update(Request $request, int $id)
    {
        $u = User::findOrFail($id);
        $data = $request->validate([
            'name'=>'sometimes|required|string|max:120',
            'email'=>"nullable|email|max:190|unique:users,email,$id",
            'phone'=>"nullable|string|max:30|unique:users,phone,$id",
            'role'=>'sometimes|required|in:admin,trainer,learner',
            'language'=>'sometimes|required|in:en,ms,ta,zh',
            'status'=>'sometimes|required|in:active,inactive'
        ]);
        if (($data['email'] ?? $u->email) === null && ($data['phone'] ?? $u->phone) === null) {
            return response()->json(['message'=>'Email or phone required'], 422);
        }
        $u->fill($data)->save();
        return response()->json(['user'=>$u]);
    }

    public function resetPassword(Request $request, int $id)
    {
        $u = User::findOrFail($id);
        $data = $request->validate(['password'=>'required|string|min:8']);
        $u->forceFill(['password'=>Hash::make($data['password'])])->save();
        return response()->json(['message'=>'Password reset']);
    }

    public function destroy(int $id)
    {
        $u = User::findOrFail($id);

        // Prevent accidental removal of the last admin
        if ($u->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Cannot delete the last admin user.'], 422);
            }
        }

        $u->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
