<?php
/**
 * 
 * @author : linhndh
 */

namespace Zalo\HttpClients;

use Zalo\Http\GraphRawResponse;
use Zalo\Exceptions\ZaloSDKException;
use Zalo\HttpClients\ZaloHttpClientInterface;

class ZaloStreamHttpClient implements ZaloHttpClientInterface
{
    /**
     * @var ZaloStream Procedural stream wrapper as object.
     */
    protected $facebookStream;

    /**
     * @param ZaloStream|null Procedural stream wrapper as object.
     */
    public function __construct(ZaloStream $facebookStream = null)
    {
        $this->facebookStream = $facebookStream ?: new ZaloStream();
    }

    /**
     * @inheritdoc
     */
    public function send($url, $method, $body, array $headers, $timeOut)
    {
        $options = [
            'http' => [
                'method' => $method,
                'header' => $this->compileHeader($headers),
                'content' => $body,
                'timeout' => $timeOut,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => true, // All root certificates are self-signed
                'cafile' => __DIR__ . '/certs/DigiCertHighAssuranceEVRootCA.pem',
            ],
        ];

        $this->facebookStream->streamContextCreate($options);
        $rawBody = $this->facebookStream->fileGetContents($url);
        $rawHeaders = $this->facebookStream->getResponseHeaders();

        if ($rawBody === false || empty($rawHeaders)) {
            throw new ZaloSDKException('Stream returned an empty response', 660);
        }

        $rawHeaders = implode("\r\n", $rawHeaders);

        return new GraphRawResponse($rawHeaders, $rawBody);
    }

    /**
     * Formats the headers for use in the stream wrapper.
     *
     * @param array $headers The request headers.
     *
     * @return string
     */
    public function compileHeader(array $headers)
    {
        $header = [];
        foreach ($headers as $k => $v) {
            $header[] = $k . ': ' . $v;
        }

        return implode("\r\n", $header);
    }
}
