<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Jackiedo\LogReader\LogReader;

class LogReaderController extends AdminController
{
    protected LogReader $reader;
    protected array $logFiles = [];

    public function __construct(LogReader $reader)
    {
        parent::__construct();

        $this->reader = $reader;
        $path = storage_path() . "/logs";

        if(File::isDirectory($path)) {
            $this->reader->setLogPath($path);
            $fileList = File::glob("$path/*.log");

            foreach ($fileList as $file) {
                if(!Str::startsWith(File::basename($file), ['.', '..']) && !Str::endsWith(File::basename($file), ['.', '..'])) {
                    $this->logFiles[] = $file;
                }
            }

            View::share('fileList', $this->logFiles);
        }
    }

    public function index()
    {
        return view('admin.log-reader.index');
    }

    public function view($id, Request $request)
    {
        if(!is_numeric($id) && $id < 1 && $id >= PHP_INT_MAX) {
            abort(403);
        }

        $logfile = collect($this->logFiles)->get($id);
        $levels = $request->has('level') ? array_filter($request->get('level')) : false;

        if($logfile) {
            $this->reader->filename(File::basename($logfile));

            if($levels) {
                $this->reader->level($levels);
            }

            $reader = $this->reader->orderBy('id', 'desc')->paginate(25, $request->get('page') ?: 0);
            return view('admin.log-reader.view', compact('reader', 'id'));
        }

        abort(404);
    }
}
