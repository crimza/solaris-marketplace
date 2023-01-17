<?php

namespace App\Console\Commands;

use App\Models\Tickets\File;
use App\Packages\Loggers\TicketImagesCleanLogger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RemoveOldTicketImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    private Carbon $keep_ticket_images_until;
    private $log;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        Carbon::setLocale('en');
        $days_to_keep_images = intval(config('catalog.tickets.days_till_images_cleanup'));

        if(!is_numeric($days_to_keep_images) || $days_to_keep_images < 1 || $days_to_keep_images > 100) {
            $days_to_keep_images = 14;
        }

        $this->keep_ticket_images_until = Carbon::now()->subDays($days_to_keep_images);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->log = new TicketImagesCleanLogger();
        $this->log->info("[~] removing thread pics older than $this->keep_ticket_images_until");
        $disk = Storage::disk('public');

        (new File)
            ->where('created_at', '<=', $this->keep_ticket_images_until)
            ->with(['message'])
            ->orderBy('id')
            ->chunk(1000, function ($files) use (&$disk) {
                foreach ($files as $file) {
                    $this->log->debug("removing old (".($file->created_at->diffForHumans(Carbon::now())).": $file->created_at) file $file->url; ticket #$file->ticket_id; message #" . ($msgId = $file->message->id ?? 0));

                    if($msgId > 0) {
                        $file->message->delete();
                    }

                    $file->remove($disk);
                }
            });
    }
}
