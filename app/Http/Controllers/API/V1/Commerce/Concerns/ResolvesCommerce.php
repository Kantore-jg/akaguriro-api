<?php

namespace App\Http\Controllers\API\V1\Commerce\Concerns;

use App\Models\Commerce;
use Illuminate\Http\Request;

trait ResolvesCommerce
{
    protected function commerce(Request $request): Commerce
    {
        return $request->attributes->get('commerce');
    }
}
