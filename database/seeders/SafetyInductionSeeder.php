<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{User,Course,Module,Lesson,Asset,Enrollment,Assessment,QuestionBank,Question,QuestionTranslation,QuestionOption,OptionTranslation};

class SafetyInductionSeeder extends Seeder
{
  public function run(): void
  {
    $admin = User::where('email','admin@example.com')->first();
    $learner = User::where('email','learner1@example.com')->first();

    $course = Course::updateOrCreate(['code'=>'SAFETY-IND-001'], [
      'status'=>'published',
      'default_language'=>'en',
      'available_languages_json'=>['en'=>true,'ms'=>true,'ta'=>true,'zh'=>true],
      'completion_rules_json'=>[
        'must_complete_all_lessons'=>true,
        'minimum_watch_percent'=>90,
        'must_view_all_slides'=>true,
        'sequential_lock'=>true
      ],
      'certificate_enabled'=>true,
      'certificate_validity_json'=>['type'=>'months','value'=>12],
      'template_id'=>DB::table('certificate_templates')->where('code','compliance_safety')->value('id'),
      'created_by'=>$admin?->id
    ]);

    $langs=['en','ms','ta','zh'];

    foreach($langs as $lang){
      DB::table('course_translations')->updateOrInsert(
        ['course_id'=>$course->id,'lang'=>$lang],
        ['title'=>'Safety Induction','description'=>'Mandatory safety induction program.']
      );
    }

    $module = Module::create(['course_id'=>$course->id,'sort_order'=>1]);
    foreach($langs as $lang){
      DB::table('module_translations')->insert([
        'module_id'=>$module->id,'lang'=>$lang,'title'=>'Site Safety & Rules'
      ]);
    }

    // Lessons
    $l1 = Lesson::create([
      'module_id'=>$module->id,'type'=>'video','sort_order'=>1,'required'=>true,'min_watch_percent'=>90,'must_view_all_slides'=>false
    ]);
    foreach($langs as $lang){
      DB::table('lesson_translations')->insert([
        'lesson_id'=>$l1->id,'lang'=>$lang,'title'=>'Safety Orientation & PPE Requirements','description'=>'Watch at least 90% to complete.'
      ]);
    }
    Asset::create([
      'lesson_id'=>$l1->id,'asset_type'=>'video','storage_driver'=>'local',
      'path_or_url'=>'demo-assets/safety-orientation.mp4','is_external'=>false,'meta_json'=>['duration_sec'=>600]
    ]);

    $l2 = Lesson::create([
      'module_id'=>$module->id,'type'=>'pdf','sort_order'=>2,'required'=>true,'must_view_all_slides'=>true
    ]);
    foreach($langs as $lang){
      DB::table('lesson_translations')->insert([
        'lesson_id'=>$l2->id,'lang'=>$lang,'title'=>'Emergency Response & Evacuation Procedures','description'=>'View all pages to complete.'
      ]);
    }
    Asset::create([
      'lesson_id'=>$l2->id,'asset_type'=>'pdf','storage_driver'=>'local',
      'path_or_url'=>'demo-assets/emergency-evacuation.pdf','is_external'=>false,'meta_json'=>['total_pages'=>8]
    ]);

    $l3 = Lesson::create([
      'module_id'=>$module->id,'type'=>'ppt','sort_order'=>3,'required'=>true,'must_view_all_slides'=>true
    ]);
    foreach($langs as $lang){
      DB::table('lesson_translations')->insert([
        'lesson_id'=>$l3->id,'lang'=>$lang,'title'=>'Hazard Awareness (Slips, Trips, Falls, Electrical)','description'=>'View all slides to complete.'
      ]);
    }
    Asset::create([
      'lesson_id'=>$l3->id,'asset_type'=>'ppt','storage_driver'=>'local',
      'path_or_url'=>'demo-assets/hazard-awareness.pptx','is_external'=>false,'meta_json'=>['total_pages'=>12]
    ]);

    // Assessment + questions
    $assessment = Assessment::updateOrCreate(['course_id'=>$course->id,'title'=>'Safety Induction Assessment'], [
      'mode'=>'quiz',
      'duration_sec'=>900,
      'attempts_limit'=>2,
      'pass_percent'=>80,
      'critical_enabled'=>true,
      'allowed_critical_mistakes'=>0,
      'shuffle_questions'=>true,
      'shuffle_options'=>true,
      'rules_json'=>['timer_mode'=>'soft_warning','warning_sec'=>120,'grace_sec'=>30]
    ]);

    $bank = QuestionBank::firstOrCreate(['assessment_id'=>$assessment->id,'name'=>'Default Bank']);

    $makeMcq = function(int $sort, bool $critical, string $text, array $options, int $correctIndex) use ($bank, $langs) {
      $q = Question::create([
        'bank_id'=>$bank->id,
        'type'=>'mcq',
        'is_critical'=>$critical,
        'points'=>1,
        'sort_order'=>$sort
      ]);
      foreach($langs as $lang){
        QuestionTranslation::create([
          'question_id'=>$q->id,
          'lang'=>$lang,
          'question_text'=>$text,
          'explanation_text'=>null
        ]);
      }
      foreach($options as $i => $optText){
        $o = QuestionOption::create([
          'question_id'=>$q->id,
          'is_correct'=>($i === $correctIndex),
          'sort_order'=>$i+1,
        ]);
        foreach($langs as $lang){
          OptionTranslation::create([
            'option_id'=>$o->id,
            'lang'=>$lang,
            'option_text'=>$optText
          ]);
        }
      }
      return $q;
    };

    $makeMcq(1, true, 'Which PPE is mandatory in the worksite?', ['Safety helmet','Sandals','No PPE needed','Earphones'], 0);
    $makeMcq(2, false, 'What is the first action in an emergency?', ['Run immediately','Stay calm and follow evacuation routes','Ignore alarms','Call friends'], 1);
    $makeMcq(3, true, 'If you see an electrical hazard, you should:', ['Touch and test','Report immediately and keep away','Continue working','Pour water'], 1);
    $makeMcq(4, false, 'Slips and trips are best prevented by:', ['Leaving cables on floor','Good housekeeping and clear walkways','Working faster','Dim lighting'], 1);
    $makeMcq(5, false, 'Assembly point is used for:', ['Lunch','Accounting','Muster during evacuation','Parking'], 2);

    Enrollment::updateOrCreate(['course_id'=>$course->id,'user_id'=>$learner->id], [
      'status'=>'assigned',
      'assigned_by'=>$admin?->id,
      'assigned_at'=>now(),
      'due_date'=>now()->addDays(14)
    ]);
  }
}
