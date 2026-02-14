<?php
/**
 * Cloudinary PHP SDK - Minimal implementation for image uploads
 * This is a lightweight alternative to the full Cloudinary SDK
 */

class Cloudinary {
    private $cloudName;
    private $apiKey;
    private $apiSecret;

    public function __construct($cloudName, $apiKey, $apiSecret) {
        $this->cloudName = $cloudName;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function uploadImage($filePath, $options = []) {
        $publicId = $options['public_id'] ?? null;
        $folder = $options['folder'] ?? null;
        $timestamp = time();

        // Build parameters
        $params = [
            'timestamp' => $timestamp,
            'upload_preset' => $options['upload_preset'] ?? null,
        ];
        
        if ($publicId) {
            $params['public_id'] = $publicId;
        }
        
        if ($folder) {
            $params['folder'] = $folder;
        }

        // Remove null values
        $params = array_filter($params, function($value) {
            return $value !== null;
        });

        // Generate signature
        $params['signature'] = $this->generateSignature($params);
        $params['api_key'] = $this->apiKey;

        // Add file
        if (function_exists('curl_file_create')) {
            $params['file'] = curl_file_create($filePath);
        } else {
            $params['file'] = '@' . $filePath;
        }

        // Upload to Cloudinary
        $url = "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Cloudinary upload failed: {$error}");
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errorMsg = $result['error']['message'] ?? 'Unknown error';
            throw new Exception("Cloudinary upload failed: {$errorMsg}");
        }
        
        return $result;
    }

    private function generateSignature($params) {
        // Sort parameters alphabetically
        ksort($params);
        
        // Build query string
        $query = [];
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[] = "{$key}={$value}";
            }
        }
        $queryString = implode('&', $query);
        
        // Generate SHA1 signature
        return sha1($queryString . $this->apiSecret);
    }

    public function url($publicId, $options = []) {
        $transformation = [];
        
        if (isset($options['width'])) {
            $transformation[] = "w_{$options['width']}";
        }
        
        if (isset($options['height'])) {
            $transformation[] = "h_{$options['height']}";
        }
        
        if (isset($options['crop'])) {
            $transformation[] = "c_{$options['crop']}";
        }
        
        if (isset($options['quality'])) {
            $transformation[] = "q_{$options['quality']}";
        }
        
        $transformStr = !empty($transformation) ? implode(',', $transformation) . '/' : '';
        
        return "https://res.cloudinary.com/{$this->cloudName}/image/upload/{$transformStr}{$publicId}";
    }
}
