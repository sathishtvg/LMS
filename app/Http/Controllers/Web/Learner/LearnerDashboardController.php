<?php
namespace App\Http\Controllers\Web\Learner;
use App\Http\Controllers\Controller;

class LearnerDashboardController extends Controller {
  public function index() { return inertia('Learner/Dashboard'); }
}
