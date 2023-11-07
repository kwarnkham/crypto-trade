<?php

namespace App\Observers;

use App\Enums\ExtractStatus;
use App\Models\Extract;
use App\Utility\Encryption;
use Illuminate\Support\Facades\Http;

class ExtractObserver
{
    /**
     * Handle the Extract "created" event.
     */
    public function created(Extract $extract): void
    {
        //
    }

    /**
     * Handle the Extract "updated" event.
     */
    public function updated(Extract $extract): void
    {
        if (in_array($extract->status, [
            ExtractStatus::COMPLETED->value,
            ExtractStatus::CANCELED->value
        ])) {
            if ($extract->agent->extract_callback) {
                Http::get($extract->agent->extract_callback, [
                    'data' => Encryption::encrypt(json_encode([
                        'id' => $extract->id,
                        'status' => $extract->status
                    ]), $extract->agent->aes_key)
                ]);
            }
        }
    }

    /**
     * Handle the Extract "deleted" event.
     */
    public function deleted(Extract $extract): void
    {
        //
    }

    /**
     * Handle the Extract "restored" event.
     */
    public function restored(Extract $extract): void
    {
        //
    }

    /**
     * Handle the Extract "force deleted" event.
     */
    public function forceDeleted(Extract $extract): void
    {
        //
    }
}
