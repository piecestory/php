<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customer photos attached to requests live on the private disk. Staff open them only through this
 * route, and only with the permission for that kind of request; anything else is a 404.
 */
class PrivateMediaController extends Controller
{
    public function __invoke(Request $request, Media $media): Response
    {
        $user = $request->user();
        $permission = match (true) {
            $media->disk !== 'local' => null,
            $media->model instanceof FinderRequest => Permission::ManageFinderRequests,
            $media->model instanceof ConsignmentRequest => Permission::ManageConsignments,
            default => null,
        };

        abort_unless($permission !== null && $user instanceof User && $user->is_active && $user->can($permission->value), 404);

        $response = $media->toInlineResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
