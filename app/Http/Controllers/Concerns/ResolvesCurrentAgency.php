<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Agency;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesCurrentAgency
{
    protected function currentAgency(Request $request): Agency
    {
        $agency = $request->user()->currentAgency();

        if (! $agency) {
            throw new HttpException(403, 'No agency is linked to this account yet.');
        }

        return $agency;
    }
}
