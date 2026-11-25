<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin Drive v3 client for the system account (scope: drive.file — the app only
 * sees files it created). Authenticates with the refresh token saved when the
 * QA office connected the account; every call goes server-side, so viewers
 * never see a Drive URL.
 */
class GoogleDrive
{
    public const FOLDER_MIME = 'application/vnd.google-apps.folder';
    private const API = 'https://www.googleapis.com';

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function isConnected(): bool
    {
        return $this->isConfigured() && filled(AppSetting::get('drive.refresh_token'));
    }

    public function connectedEmail(): ?string
    {
        return AppSetting::get('drive.email');
    }

    public const GMAIL_SEND_SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    public function saveConnection(string $email, string $refreshToken, array $scopes = []): void
    {
        AppSetting::put('drive.email', $email);
        AppSetting::putSecret('drive.refresh_token', $refreshToken);
        AppSetting::put('drive.scopes', implode(' ', $scopes));
        Cache::forget('drive.access_token');
    }

    /** The same connection also sends the system's no-reply emails, once gmail.send was granted. */
    public function canSendMail(): bool
    {
        return $this->isConnected() && str_contains((string) AppSetting::get('drive.scopes'), self::GMAIL_SEND_SCOPE);
    }

    /** Send a complete RFC 822 message as the connected account (Gmail API). */
    public function sendRawEmail(string $message): void
    {
        $raw = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
        $this->api()->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $raw])->throw();
    }

    public function disconnect(): void
    {
        AppSetting::put('drive.refresh_token', null);
        Cache::forget('drive.access_token');
    }

    public function createFolder(string $name, ?string $parentId): string
    {
        return $this->api()->post(self::API . '/drive/v3/files', array_filter([
            'name' => $name,
            'mimeType' => self::FOLDER_MIME,
            'parents' => $parentId ? [$parentId] : null,
        ]))->throw()->json('id');
    }

    public function rename(string $id, string $name): void
    {
        $this->api()->patch(self::API . "/drive/v3/files/{$id}", ['name' => $name])->throw();
    }

    public function trash(string $id): void
    {
        $response = $this->api()->patch(self::API . "/drive/v3/files/{$id}", ['trashed' => true]);
        if ($response->status() !== 404) {
            $response->throw();
        }
    }

    /** Resumable upload, streamed from disk so large videos don't sit in memory. */
    public function upload(string $absolutePath, string $name, ?string $mime, string $parentId): string
    {
        $size = filesize($absolutePath);
        $session = $this->api()
            ->withHeaders(['X-Upload-Content-Type' => $mime ?: 'application/octet-stream', 'X-Upload-Content-Length' => (string) $size])
            ->post(self::API . '/upload/drive/v3/files?uploadType=resumable&fields=id', ['name' => $name, 'parents' => [$parentId]])
            ->throw()->header('Location');

        if (! $session) {
            throw new RuntimeException('Drive did not return an upload session.');
        }

        $stream = fopen($absolutePath, 'rb');
        try {
            return $this->api()->timeout(0)
                ->withHeaders(['Content-Type' => $mime ?: 'application/octet-stream', 'Content-Length' => (string) $size])
                ->send('PUT', $session, ['body' => $stream])
                ->throw()->json('id');
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /** File bytes as a PSR-7 response (supports Range for video seeking). */
    public function download(string $id, ?string $range = null)
    {
        return $this->api()->timeout(0)
            ->withOptions(['stream' => true])
            ->withHeaders($range ? ['Range' => $range] : [])
            ->get(self::API . "/drive/v3/files/{$id}", ['alt' => 'media'])
            ->throw()
            ->toPsrResponse();
    }

    private function api()
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout(60);
    }

    private function accessToken(): string
    {
        if (! $this->isConnected()) {
            throw new RuntimeException('Google Drive is not connected.');
        }

        return Cache::remember('drive.access_token', now()->addMinutes(50), function () {
            return Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => AppSetting::getSecret('drive.refresh_token'),
                'grant_type' => 'refresh_token',
            ])->throw()->json('access_token');
        });
    }
}
