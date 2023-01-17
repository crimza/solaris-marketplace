<?php

namespace App\Http\Requests;

use App\Models\Tickets\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class TicketAddRequest extends FormRequest
{
    /**
     * @var ImageManager
     */
    protected $im;
    /**
     * @var string[]
     */
    public $ticketImages = [];
    /**
     * @var int
     */
    protected $maxWidth = 1024;
    /**
     * @var int
     */
    protected $maxHeight = 1024;

    /**
     * ShopGoodsAddRequest constructor.
     * @param ImageManager $im
     */
    public function __construct(ImageManager $im)
    {
        parent::__construct();
        $this->im = $im;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

        $validator->after(function($validator) {
            if ($validator->errors()->count() > 0) {
                return;
            }

            if ($this->file('images') && count($this->file('images')) > 3) {
                $validator->errors()->add('images', 'Загружено слишком много дополнительных картинок.');
            }

            if ($this->hasFile('images')) {
                foreach ($this->file('images') as $image) {
                    $fileName = Str::random(32);
                    $folderName = substr($fileName, 0, 2);
                    $path = sprintf('ticket_images/%s/%s.jpeg', $folderName, $fileName);
                    $thumbnailPath = sprintf('ticket_images/%s/thumbs/%s.jpeg', $folderName, $fileName);
                    $disk = \Storage::disk('public');

                    try {
                        $disk->put($path, $this->formatTicketImage($image));
                        $disk->put($thumbnailPath, $this->formatThumbnail($image));
                    } catch (\Exception $e) {
                        $validator->errors()->add('images', 'Невозможно сохранить картинку.');
                        return;
                    }

                    $this->ticketImages[] = $disk->url($path);
                }
            }
        });

        return $validator;
    }

    public function rules()
    {
        $rules = [];

        if(!$this->hasFile('images') || !$this->route('ticketId')) {
            $rules['message'] = 'required|min:3|max:10000';
        }

        if($this->hasFile('images')) {
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg|max:5120';
        }

        // проверяем заголовок и категорию, если это новый тикет
        if(!$this->route('ticketId')) {
            $rules['title'] = 'required|min:3|max:128';
            $rules['category'] = 'required|in:'.implode(',', [
                    Ticket::CATEGORY_COMMON_SELLER_QUESTION,
                    Ticket::CATEGORY_COMMON_BUYER_QUESTION,
                    Ticket::CATEGORY_APPLICATION_FOR_OPENING,
                    Ticket::CATEGORY_COOPERATION,
                    Ticket::CATEGORY_SECURITY_SERVICE,
                    Ticket::CATEGORY_PAYMENT_ERRORS
                ]);
        }

        return $rules;
    }

    private function formatThumbnail($file): string
    {
        return (string) $this->im
            ->make($file->path())
            ->fit(200, 200, null, 'center')
            ->encode('jpg');
    }

    protected function formatTicketImage($file): string
    {
        $image = $this->im->make($file->path());
        if (($shouldChangeWidth = $image->width() > $this->maxWidth) || $image->height() > $this->maxHeight) {
            $targetWidth = $shouldChangeWidth ? $this->maxWidth : null;
            $targetHeight = $shouldChangeWidth ? null : $this->maxHeight;
            $image->resize($targetWidth, $targetHeight, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        return (string) $image->encode('jpg');
    }
}
