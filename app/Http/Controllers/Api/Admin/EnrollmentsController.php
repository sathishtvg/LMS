<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::with([
            'user:id,name,email,phone,role',
            'course:id,code,default_language',
            'course.translations:course_id,lang,title'
        ])->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $s = '%'.$request->string('search').'%';
            $query->where(function($q) use ($s) {
                $q->whereHas('user', function($uq) use ($s) {
                    $uq->where('name','like',$s)
                       ->orWhere('email','like',$s)
                       ->orWhere('phone','like',$s);
                })->orWhereHas('course', function($cq) use ($s) {
                    $cq->where('code','like',$s);
                });
            });
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'nullable|in:assigned,in_progress,completed,archived',
            'due_date' => 'nullable|date',
        ]);

        $en = Enrollment::firstOrCreate(
            ['course_id' => $data['course_id'], 'user_id' => $data['user_id']],
            [
                'status' => $data['status'] ?? 'assigned',
                'assigned_by' => optional($request->user())->id,
                'assigned_at' => now(),
                'due_date' => $data['due_date'] ?? null,
            ]
        );

        return response()->json(['enrollment' => $en->loadMissing(['user','course'])], 201);
    }

    public function update(Request $request, int $id)
    {
        $en = Enrollment::findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|required|in:assigned,in_progress,completed,archived',
            'due_date' => 'nullable|date',
        ]);

        $en->fill($data)->save();
        return response()->json(['enrollment' => $en]);
    }

    public function destroy(int $id)
    {
        $en = Enrollment::findOrFail($id);
        $en->delete();
        return response()->json(['message' => 'Enrollment deleted']);
    }
}