<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class Request extends FormRequest
{
    /**
    * Throw 403 HTTP-error on failed authorize method.
    *
    * @var bool
     */
    protected $accessDeniedHttpException = true;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $result = $this->checkAuthorized();

        if (!$result && $this->accessDeniedHttpException) {
            throw new AccessDeniedHttpException();
        }

        return $result;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected function checkAuthorized()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }
}