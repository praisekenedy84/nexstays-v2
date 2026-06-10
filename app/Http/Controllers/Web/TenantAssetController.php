<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TenantAssetController extends Controller
{
    public function serve(Request $request, string $tenantId, string $path): BinaryFileResponse
    {
        $tenant = Tenant::find($tenantId);

        abort_if($tenant === null, 404);

        tenancy()->initialize($tenant);

        try {
            $disk = (string) config('filesystems.default', 'public');
            $disk = $disk === 'local' ? 'public' : $disk;

            $root = config("filesystems.disks.{$disk}.root");

            abort_if($root === null, 404);

            $allowedRoot = realpath($root);
            abort_if($allowedRoot === false, 404);

            $fullPath = realpath("{$allowedRoot}/{$path}");
            abort_if($fullPath === false, 404);
            abort_unless(str_starts_with($fullPath, $allowedRoot), 404);

            return response()->file($fullPath);
        } finally {
            tenancy()->end();
        }
    }
}
