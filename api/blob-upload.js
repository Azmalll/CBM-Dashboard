import {
    handleUpload
} from '@vercel/blob/client';

export default async function handler(req, res) {

    if (req.method !== 'POST') {
        return res.status(405).json({
            error: 'Method Not Allowed'
        });
    }

    try {

        const body = req.body;

        const jsonResponse = await handleUpload({

            body,

            request: req,

            onBeforeGenerateToken:
                async (
                    pathname,
                    clientPayload
                ) => {

                    /*
                     * Pastikan hanya ODX yang boleh
                     * menggunakan endpoint ini.
                     */
                    if (
                        !pathname.startsWith(
                            'odx-temp/'
                        )
                    ) {
                        throw new Error(
                            'Invalid ODX pathname.'
                        );
                    }

                    /*
                     * Pastikan extension ODX.
                     */
                    if (
                        !pathname
                            .toLowerCase()
                            .endsWith('.odx')
                    ) {
                        throw new Error(
                            'Only .odx files are allowed.'
                        );
                    }

                    return {

                        allowedContentTypes: [
                            'application/octet-stream',
                            'application/xml',
                            'text/xml',
                            'text/plain'
                        ],

                        maximumSizeInBytes:
                            50 * 1024 * 1024,

                        addRandomSuffix: false,

                        allowOverwrite: false,

                        tokenPayload:
                            clientPayload || ''
                    };
                },

            onUploadCompleted:
                async ({
                    blob,
                    tokenPayload
                }) => {

                    console.log(
                        'ODX upload completed:',
                        blob.pathname
                    );

                }
        });

        return res
            .status(200)
            .json(jsonResponse);

    } catch (error) {

        console.error(
            'Vercel Blob upload error:',
            error
        );

        return res.status(400).json({
            error:
                error instanceof Error
                    ? error.message
                    : 'Blob upload failed.'
        });
    }
}