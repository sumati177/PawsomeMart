<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Vercel Stateless Session Polyfill
    if (empty($_SESSION) && isset($_COOKIE['app_sess'])) {
        $decoded = base64_decode($_COOKIE['app_sess']);
        $uncompressed = @gzuncompress($decoded);
        $sess_data = json_decode($uncompressed ?: $decoded, true); // Fallback to uncompressed if legacy
        if (is_array($sess_data)) $_SESSION = $sess_data;
    }
}

// ============================================
// � LOAD ENVIRONMENT VARIABLES
// ============================================

// Load .env file
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set as environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// ============================================
// 🔥 FIREBASE CONFIGURATION
// ============================================

// Firebase Firestore Project ID
define('FIREBASE_PROJECT_ID', getenv('FIREBASE_PROJECT_ID') ?: 'pawsomemart1');
define('FIREBASE_API_KEY', getenv('FIREBASE_API_KEY') ?: 'AIzaSyB-ntkPWJ2QFTKqhoINppEXMUPn8eSN11g');

// ============================================
// ☁️ CLOUDINARY CONFIGURATION
// ============================================

define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'dxr1vepkt');
define('CLOUDINARY_UPLOAD_PRESET', getenv('CLOUDINARY_UPLOAD_PRESET') ?: 'pawsomemart_upload');
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY') ?: '928276744371939');

// ============================================
// 🔧 FIREBASE AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Make Firebase Authentication REST API requests
 */
function firebase_auth_request($endpoint, $data)
{
    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:' . $endpoint . '?key=' . FIREBASE_API_KEY;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    
    if ($httpCode >= 400) {
        return [
            'error' => true,
            'status' => $httpCode,
            'message' => $decoded['error']['message'] ?? 'Auth error'
        ];
    }
    
    return $decoded;
}

// ============================================
// 🔧 FIRESTORE REST API FUNCTIONS
// ============================================

/**
 * Generic Firestore REST API request
 */
function firestore_request($method, $path, $data = null)
{
    $url = 'https://firestore.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/databases/(default)/documents' . $path;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['error' => true, 'message' => 'cURL Error: ' . $curlError];
    }
    
    $decoded = json_decode($response, true);
    
    if ($httpCode >= 400) {
        return [
            'error' => true,
            'status' => $httpCode,
            'message' => $decoded['error']['message'] ?? 'Firestore error'
        ];
    }
    
    return $decoded;
}

/**
 * Add a document to Firestore collection
 */
function firestore_add($collection, $data)
{
    $path = '/' . $collection;
    $payload = ['fields' => firestore_encode_data($data)];
    $result = firestore_request('POST', $path, $payload);
    
    if (isset($result['name'])) {
        preg_match('/\/([^\/]+)$/', $result['name'], $matches);
        return ['success' => true, 'id' => $matches[1] ?? '', 'data' => $result];
    }
    
    return ['error' => true, 'message' => $result['message'] ?? 'Failed to add document'];
}

/**
 * Get a document from Firestore
 */
function firestore_get($collection, $docId)
{
    $path = '/' . $collection . '/' . $docId;
    $result = firestore_request('GET', $path);
    
    if (isset($result['fields'])) {
        return firestore_decode_data($result['fields']);
    }
    
    return isset($result['error']) ? $result : null;
}

/**
 * Get all documents from a Firestore collection
 */
function firestore_get_all($collection)
{
    $path = '/' . $collection . '?pageSize=1000';
    $result = firestore_request('GET', $path);
    
    $documents = [];
    if (isset($result['documents']) && is_array($result['documents'])) {
        foreach ($result['documents'] as $doc) {
            if (isset($doc['fields'])) {
                preg_match('/\/([^\/]+)$/', $doc['name'], $matches);
                $documents[$matches[1] ?? ''] = firestore_decode_data($doc['fields']);
            }
        }
    }
    
    return $documents;
}

/**
 * Update a document in Firestore
 */
function firestore_update($collection, $docId, $data)
{
    $path = '/' . $collection . '/' . $docId;
    $payload = ['fields' => firestore_encode_data($data)];
    $result = firestore_request('PATCH', $path, $payload);
    
    return isset($result['fields']) ? ['success' => true, 'data' => $result] : 
           ['error' => true, 'message' => $result['message'] ?? 'Failed to update document'];
}

/**
 * Delete a document from Firestore
 */
function firestore_delete($collection, $docId)
{
    $path = '/' . $collection . '/' . $docId;
    $result = firestore_request('DELETE', $path);
    
    return isset($result['error']) ? $result : ['success' => true];
}

/**
 * Encode PHP data to Firestore format
 */
function firestore_encode_data($data)
{
    $encoded = [];
    
    foreach ($data as $key => $value) {
        if ($value === null) {
            $encoded[$key] = ['nullValue' => null];
        } elseif (is_bool($value)) {
            $encoded[$key] = ['booleanValue' => $value];
        } elseif (is_int($value) || is_float($value)) {
            $encoded[$key] = ['doubleValue' => (float)$value];
        } elseif (is_string($value)) {
            $encoded[$key] = ['stringValue' => $value];
        } elseif (is_array($value)) {
            
            // Custom type handlers (e.g. Firestore Timestamp)
            if (isset($value['timestampValue'])) {
                $encoded[$key] = ['timestampValue' => $value['timestampValue']];
                continue;
            }
            if (isset($value['timestamp_utc'])) {
                $encoded[$key] = ['timestampValue' => $value['timestamp_utc']];
                continue;
            }

            // Check if it's a list or associative array
            $is_list = function_exists('array_is_list') ? array_is_list($value) : (array_keys($value) === range(0, count($value) - 1));
            
            if ($is_list) {
                $arrayValues = [];
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $arrayValues[] = ['mapValue' => ['fields' => firestore_encode_data($item)]];
                    } else {
                        $encoded_item = firestore_encode_data(['v' => $item]);
                        $arrayValues[] = $encoded_item['v'];
                    }
                }
                $encoded[$key] = ['arrayValue' => ['values' => $arrayValues]];
            } else {
                $encoded[$key] = ['mapValue' => ['fields' => firestore_encode_data($value)]];
            }
        }
    }
    
    return $encoded;
}

/**
 * Decode Firestore format to PHP data
 */
function firestore_decode_data($fields)
{
    $decoded = [];
    
    foreach ($fields as $key => $field) {
        if (isset($field['nullValue'])) {
            $decoded[$key] = null;
        } elseif (isset($field['booleanValue'])) {
            $decoded[$key] = $field['booleanValue'];
        } elseif (isset($field['doubleValue'])) {
            $decoded[$key] = $field['doubleValue'];
        } elseif (isset($field['integerValue'])) {
            $decoded[$key] = (int)$field['integerValue'];
        } elseif (isset($field['stringValue'])) {
            $decoded[$key] = $field['stringValue'];
        } elseif (isset($field['timestampValue'])) {
            $decoded[$key] = $field['timestampValue'];
        } elseif (isset($field['arrayValue'])) {
            $decoded[$key] = [];
            if (isset($field['arrayValue']['values'])) {
                foreach ($field['arrayValue']['values'] as $item) {
                    if (isset($item['mapValue']['fields'])) {
                        $decoded[$key][] = firestore_decode_data($item['mapValue']['fields']);
                    } else {
                        // Extract primitive array items correctly without nesting
                        $res = firestore_decode_data(['_temp' => $item]);
                        if (isset($res['_temp'])) {
                            $decoded[$key][] = $res['_temp'];
                        }
                    }
                }
            }
        } elseif (isset($field['mapValue']['fields'])) {
            $decoded[$key] = firestore_decode_data($field['mapValue']['fields']);
        }
    }
    
    return $decoded;
}

// ============================================
// ☁️ CLOUDINARY UPLOAD FUNCTION
// ============================================

/**
 * Upload image to Cloudinary
 */
function cloudinary_upload($file_path, $public_id = '')
{
    if (!file_exists($file_path)) {
        return ['error' => true, 'message' => 'File not found'];
    }
    
    $ch = curl_init('https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload');
    
    $post_data = [
        'file' => new CURLFile($file_path),
        'upload_preset' => CLOUDINARY_UPLOAD_PRESET
    ];
    
    if (!empty($public_id)) {
        $post_data['public_id'] = $public_id;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    $json = json_decode($result, true);
    
    if (isset($json['secure_url'])) {
        return ['success' => true, 'url' => $json['secure_url'], 'public_id' => $json['public_id']];
    }
    
    return ['error' => true, 'message' => $json['error']['message'] ?? 'Upload failed'];
}

// ============================================
// 👤 HELPER FUNCTIONS
// ============================================

/**
 * Custom Session Saving Redirect
 */
function app_redirect($url) {
    if (!headers_sent()) {
        $sess_str = json_encode($_SESSION ?? []);
        $compressed = function_exists('gzcompress') ? gzcompress($sess_str) : $sess_str;
        setcookie('app_sess', base64_encode($compressed), time() + 86400 * 7, '/');
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

/**
 * Check if user is logged in (alias for is_logged_in)
 */
function is_user()
{
    return is_logged_in();
}

/**
 * Check if admin is logged in
 */
function is_admin()
{
    return isset($_SESSION['admin']) && isset($_SESSION['admin']['id']);
}

/**
 * Get current user data
 */
function app_get_current_user()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Get current admin data
 */
function get_current_admin()
{
    return $_SESSION['admin'] ?? null;
}
?>