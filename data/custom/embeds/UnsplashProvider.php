<?php

namespace Directus\Embed\Provider;

use Directus\Application\Application;
use function Directus\generate_uuid4;

class UnsplashProvider extends AbstractProvider
{
    protected $name = 'Unsplash';

    public function getProviderType()
    {
        return 'image';
    }

    /**
     * @inheritDoc
     */
    public function validateURL($url)
    {
        return strpos($url, 'unsplash.com') !== false;
    }

    /**
     * @inheritdoc
     */
    public function getFormatUrl()
    {
        return 'https://unsplash.com/photos/{{embed}}';
    }

    /**
     * @inheritDoc
     */
    protected function parseURL($url)
    {
        // Get ID from URL
        preg_match('/unsplash\.com\/photos\/([0-9a-zA-Z_-]{11})/', $url, $matches);
        $imageID = isset($matches[1]) ? $matches[1] : null;

        // Can't find the image ID
        if (!$imageID) {
            throw new \Exception('Unsplash image ID not detected');
        }

        return $imageID;
    }

    /**
     * Fetch Image information
     * @param $imageID
     * @return array
     */
    protected function fetchInfo($imageID)
	{
        $app = Application::getInstance();
		$url = 'https://unsplash.com/photos/' . $imageID . '/download';

        stream_context_set_default([
            'http' => [
                'method' => 'HEAD',
            ]
        ]);

        $urlHeaders = get_headers($url, 1);

        stream_context_set_default([
            'http' => [
                'method' => 'GET',
            ]
        ]);

        $info = [];

        $contentType = $urlHeaders['Content-Type'];
        if (is_array($contentType)) {
            $contentType = end($contentType);
        }

        $contentType = $this->getMimeTypeFromContentType($contentType);

        $content = file_get_contents($url);
        if (!$content) {
            return $info;
        }

        $info['description'] = 'https://unsplash.com/photos/' . $imageID;
        $info['title'] = $imageID;
        $info['tags'] = ''; //'example,tags,here';
        $info['location'] = ''; //'Example';

        // Set the IPTC tags
        $iptc = array(
            '2#120' => $info['description'],
            '2#005' => $info['title'],
            '2#025' => $info['tags'],
            '2#090' => $info['location'],
        );

        // Convert the IPTC tags into binary code
        $iptc_binary = '';

        foreach($iptc as $tag => $string)
        {
            $tag = substr($tag, 2);
            $iptc_binary .= $this->iptc_make_tag(2, $tag, $string);
        }

        $tmp_file_path = '/tmp/' . generate_uuid4();
        file_put_contents($tmp_file_path, $content); 
        $embedded_content = iptcembed($iptc_binary, $tmp_file_path);
        unlink($tmp_file_path);

        list($width, $height) = getimagesizefromstring($embedded_content);

        $data = base64_encode($embedded_content);
        $info['filename'] = $imageID . '.jpg';
        $info['name'] = $imageID;
        $info['filesize'] = isset($urlHeaders['Content-Length']) ? $urlHeaders['Content-Length'] : 0;
        $info['type'] = $contentType;
        $info['width'] = $width;
        $info['height'] = $height;
        $info['data'] = $data;
        $info['charset'] = 'binary';

        return $info;
    }

    /**
     * Gets the mime-type from the content type
     *
     * @param $contentType
     *
     * @return string
     */
    protected function getMimeTypeFromContentType($contentType)
    {
        // split the data type if it has charset or boundaries set
        // ex: image/jpg;charset=UTF8
        if (strpos($contentType, ';') !== false) {
            $contentType = array_map('trim', explode(';', $contentType));
        }

        if (is_array($contentType)) {
            $contentType = $contentType[0];
        }

        return $contentType;
    }

    // iptc_make_tag() function by Thies C. Arntzen
    protected function iptc_make_tag($rec, $data, $value)
    {
        $length = strlen($value);
        $retval = chr(0x1C) . chr($rec) . chr($data);

        if($length < 0x8000)
        {
            $retval .= chr($length >> 8) .  chr($length & 0xFF);
        }
        else
        {
            $retval .= chr(0x80) .
                       chr(0x04) .
                       chr(($length >> 24) & 0xFF) .
                       chr(($length >> 16) & 0xFF) .
                       chr(($length >> 8) & 0xFF) .
                       chr($length & 0xFF);
        }

        return $retval . $value;
    }

}
