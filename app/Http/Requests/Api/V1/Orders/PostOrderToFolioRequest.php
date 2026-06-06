<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Services\OrderAuthorizationService;
use Illuminate\Foundation\Http\FormRequest;

class PostOrderToFolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $order = $this->route('order');

        return $user !== null
            && $user->can('post-folio-charges')
            && $order instanceof Order
            && app(OrderAuthorizationService::class)->canManage($user, $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'folio_id' => ['required', 'uuid', 'exists:folios,id'],
        ];
    }
}
