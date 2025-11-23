<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use HTMLPurifier;
use HTMLPurifier_Config;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'user_id' => 'prohibited',
        ];
    }

    /**
     * Prepare/sanitize data BEFORE validation
     */
    protected function prepareForValidation()
    {
        if ($this->has('content')) {
            $config = HTMLPurifier_Config::createDefault();
    
            $config->set('HTML.Allowed', 'p,br,strong,em,i,u,ul,ol,li,blockquote,code,pre,h1,h2,h3,h4,h5,h6,a[href|title]');
            $config->set('HTML.ForbiddenElements', ['img', 'script', 'iframe', 'object', 'embed']);
            
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('URI.DisableExternalResources', true);
            $config->set('URI.DisableResources', true);
            $config->set('AutoFormat.Linkify', false);
            $config->set('AutoFormat.RemoveEmpty', true);
    
            $purifier = new HTMLPurifier($config);
            $sanitized = $purifier->purify($this->input('content'));
    
            if (preg_match('/javascript:/i', $sanitized)) {
                $this->merge(['content' => '']);
            } else {
                $this->merge([
                    'title' => htmlspecialchars(strip_tags($this->input('title'))),
                    'content' => $sanitized,
                ]);
            }
        }
    }
}