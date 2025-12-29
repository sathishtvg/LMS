<?php
namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;

class VerifyCertificateController extends Controller
{
    public function verify(string $token)
    {
        $cert = Certificate::where('verification_token',$token)->first();
        if (!$cert) return response()->json(['valid'=>false], 404);
        return response()->json([
          'valid'=>true,
          'certificate_no'=>$cert->certificate_no,
          'issued_at'=>$cert->issued_at,
          'expires_at'=>$cert->expires_at,
          'status'=>$cert->status
        ]);
    }
}
