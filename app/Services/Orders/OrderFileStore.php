<?php

namespace App\Services\Orders;

use App\Events\DeskEvent;
use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderFileStore
{
    public const ALLOWED = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'png', 'jpg', 'jpeg', 'zip'];

    public function store(Order $order, int $uploaderId, UploadedFile $upload, string $description = ''): OrderFile
    {
        $this->assertOutsideWebRoot();

        $extension = strtolower($upload->getClientOriginalExtension() ?: $upload->extension() ?: 'bin');
        if (! in_array($extension, self::ALLOWED, true)) {
            throw new RuntimeException('That file type is not accepted.');
        }

        $safe = Str::slug(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $source = $order->order_id.'/'.Str::lower(Str::random(12)).'_'.$safe.'.'.$extension;

        $stored = $this->disk()->put($source, fopen($upload->getRealPath(), 'r'));
        if ($stored === false) {
            throw new RuntimeException('The file could not be stored.');
        }

        $file = new OrderFile;
        $file->order_id = $order->order_id;
        $file->site_id = $order->site_id;
        $file->uploader_id = $uploaderId;
        $file->file_type_id = 1;
        $file->file_description = Str::limit($description, 250, '');
        $file->name = $upload->getClientOriginalName();
        $file->size = (int) $upload->getSize();
        $file->source = $source;
        $file->extension = $extension;
        $file->date_add = now();
        $file->date_upd = now();
        $file->save();

        DeskEvent::dispatch('order.file', (int) $order->order_id, ['file_id' => (int) $file->file_id]);

        return $file;
    }

    public function download(OrderFile $file): StreamedResponse
    {
        $this->assertOutsideWebRoot();
        $path = $this->absolutePath($file->source);

        if (! config('legacy.read_only')) {
            $file->downloaded = 1;
            $file->downloaded_at = now();
            $file->save();
        }

        return $this->disk()->download($file->source, $file->name);
    }

    public function absolutePath(string $source): string
    {
        $source = str_replace('\\', '/', $source);
        if (str_contains($source, '..') || str_starts_with($source, '/')) {
            throw new RuntimeException('That file path is not allowed.');
        }

        $root = $this->root();
        $full = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $source);
        $realRoot = realpath($root);
        $real = realpath($full);

        if ($real === false || $realRoot === false || ! str_starts_with($real, $realRoot.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('That file is not in the uploads folder.');
        }

        return $real;
    }

    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => $this->root(),
        ]);
    }

    public function root(): string
    {
        $root = (string) config('legacy.uploads_path');
        if (! is_dir($root)) {
            mkdir($root, 0750, true);
        }

        return $root;
    }

    public function assertOutsideWebRoot(): void
    {
        $root = realpath($this->root());
        $public = realpath(public_path());

        if ($root && $public && (str_starts_with($root, $public.DIRECTORY_SEPARATOR) || $root === $public)) {
            throw new RuntimeException('Order files must be stored outside the web root.');
        }
    }
}
