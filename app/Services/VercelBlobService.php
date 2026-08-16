<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class VercelBlobService
{
    private Client $http;

    private string $token;

    private string $storeId;

    private string $apiUrl =
        'https://vercel.com/api/blob';

    public function __construct()
    {
        $this->token =
            trim(
                (string) env(
                    'BLOB_READ_WRITE_TOKEN'
                )
            );

        $this->storeId =
            trim(
                (string) env(
                    'BLOB_STORE_ID'
                )
            );

        if ($this->token === '') {
            throw new RuntimeException(
                'BLOB_READ_WRITE_TOKEN belum tersedia.'
            );
        }

        if ($this->storeId === '') {
            throw new RuntimeException(
                'BLOB_STORE_ID belum tersedia.'
            );
        }

        if (
            str_starts_with(
                $this->storeId,
                'store_'
            )
        ) {
            $this->storeId =
                substr(
                    $this->storeId,
                    6
                );
        }

        $this->http =
            new Client([
                'timeout' =>
                    120,

                'connect_timeout' =>
                    15,
            ]);
    }

    /**
     * Upload file ke Vercel Blob private store.
     */
    public function upload(
        string $pathname,
        string $filePath,
        string $contentType =
            'application/pdf'
    ): array {

        if (!is_file($filePath)) {
            throw new RuntimeException(
                "File tidak ditemukan: {$filePath}"
            );
        }

        $url =
            $this->apiUrl .
            '?' .
            http_build_query([
                'pathname' =>
                    $pathname,
            ]);

        try {

            $response =
                $this->http->request(
                    'PUT',
                    $url,
                    [
                        'headers' => [

                            'Authorization' =>
                                'Bearer ' .
                                $this->token,

                            'x-vercel-blob-store-id' =>
                                $this->storeId,

                            'x-api-version' =>
                                '12',

                            'x-vercel-blob-access' =>
                                'private',

                            'x-content-type' =>
                                $contentType,

                            'x-add-random-suffix' =>
                                'false',

                            'x-allow-overwrite' =>
                                'true',
                        ],

                        'body' =>
                            fopen(
                                $filePath,
                                'rb'
                            ),
                    ]
                );

        } catch (GuzzleException $e) {

            throw new RuntimeException(
                'Gagal menghubungi Vercel Blob: ' .
                $e->getMessage(),
                previous: $e
            );
        }

        $body =
            (string) $response->getBody();

        $data =
            json_decode(
                $body,
                true
            );

        if (!is_array($data)) {
            throw new RuntimeException(
                'Response Vercel Blob tidak valid: ' .
                $body
            );
        }

        return [
            'pathname' =>
                (string) (
                    $data['pathname']
                    ?? $pathname
                ),

            'url' =>
                (string) (
                    $data['url']
                    ?? ''
                ),

            'downloadUrl' =>
                (string) (
                    $data['downloadUrl']
                    ?? ''
                ),

            'contentType' =>
                (string) (
                    $data['contentType']
                    ?? $contentType
                ),

            'etag' =>
                (string) (
                    $data['etag']
                    ?? ''
                ),
        ];
    }

    /**
     * Ambil private blob sebagai response stream.
     */
    public function get(string $pathname)
    {
        $blobUrl =
            $this->blobUrl(
                $pathname
            );

        try {

            return $this->http->request(
                'GET',
                $blobUrl,
                [
                    'headers' => [
                        'Authorization' =>
                            'Bearer ' .
                            $this->token,
                    ],

                    'stream' =>
                        true,
                ]
            );

        } catch (GuzzleException $e) {

            throw new RuntimeException(
                'Gagal mengambil file dari Vercel Blob: ' .
                $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Hapus private blob.
     */
    public function delete(
        string $pathname
    ): void {

        $blobUrl =
            $this->blobUrl(
                $pathname
            );

        try {

            $this->http->request(
                'POST',
                $this->apiUrl .
                    '/delete',
                [
                    'headers' => [

                        'Authorization' =>
                            'Bearer ' .
                            $this->token,

                        'x-vercel-blob-store-id' =>
                            $this->storeId,

                        'x-api-version' =>
                            '12',

                        'Content-Type' =>
                            'application/json',
                    ],

                    'json' => [
                        'urls' => [
                            $blobUrl
                        ],
                    ],
                ]
            );

        } catch (GuzzleException $e) {

            throw new RuntimeException(
                'Gagal menghapus file dari Vercel Blob: ' .
                $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Bentuk URL private blob.
     */
    public function blobUrl(
        string $pathname
    ): string {

        return sprintf(
            'https://%s.private.blob.vercel-storage.com/%s',
            $this->storeId,
            ltrim(
                $pathname,
                '/'
            )
        );
    }
}