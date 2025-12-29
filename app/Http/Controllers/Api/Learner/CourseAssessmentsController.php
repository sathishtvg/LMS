<?php
namespace App\Http\Controllers\Api\Learner;

use App\Http\Controllers\Controller;
use App\Models\{Course,Assessment,Enrollment};
use Illuminate\Http\Request;

class CourseAssessmentsController extends Controller
{
  public function index(Request $request, int $courseId)
  {
    // learner can only view assessments for courses they are enrolled in
    $en = Enrollment::where('course_id',$courseId)->where('user_id',$request->user()->id)->first();
    if (!$en) abort(403,'Forbidden');

    $items = Assessment::where('course_id',$courseId)->orderBy('id')->get(['id','title','duration_sec','pass_percent','attempts_limit']);
    return response()->json($items);
  }
}
