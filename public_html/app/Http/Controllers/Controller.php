<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;
use App\Models\OpEventLog;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $program_name = '';
    protected $route_name = '';

    /**
     * add event log
     *
     * @param string $params json string
     * @return void
     */
    public function event_log($params = "") {
        $log = new OpEventLog;
        $log->user_id = Auth::user()->id;
        $log->action = Route::getCurrentRoute()->getActionName();
        $log->guard = Auth::getDefaultDriver();
        $log->name = Auth::user()->name;
        $log->params = $params;
        $log->level = 0;
        $log->save();
    }

    protected function dataTableJsonResponse($draw, $totalRecords, $totalRecordswithFilter, $otherData) {
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter
        );

        $result = $response + $otherData;
        return response()->json($result, 200,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    public function upload($file, $slug = '') {
        $fullFilename = null;
        $resizeWidth = 1800;
        $resizeHeight = null;

        $path = $slug.'/'.date('F').date('Y').'/';

        $OriginalName = Str::random(32);
        $filename = basename($OriginalName, '.'.$file->getClientOriginalExtension());
        $filename_counter = 1;
        // Make sure the filename does not exist, if it does make sure to add a number to the end 1, 2, 3, etc...
        while (Storage::disk(config('_constant.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
            $filename = basename($OriginalName, '.'.$file->getClientOriginalExtension()).(string) ($filename_counter++);
        }

        $fullPath = $path.$filename.'.'.$file->getClientOriginalExtension();

        $ext = $file->guessClientExtension();

        if (in_array($ext, ['jpeg', 'jpg', 'png', 'gif'])) {
            $image = Image::make($file)
                ->resize($resizeWidth, $resizeHeight, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            if ($ext !== 'gif') {
                $image->orientate();
            }
            $image->encode($file->getClientOriginalExtension(), 75);

            // move uploaded file from temp to uploads directory
            if (Storage::disk(config('_constant.storage.disk'))->put($fullPath, (string) $image, 'public')) {
                $fullFilename = $fullPath;
            } else {
                Log::info('Storage Error');
                return false;
            }
        } else {
            Log::info($ext. ' Error');
            return false;
        }

        // echo out script that TinyMCE can handle and update the image in the editor
        return $fullFilename;
    }
}
