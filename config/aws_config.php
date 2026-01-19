<?php
/**
 * AWS Rekognition Configuration
 * Handles AWS SDK initialization and face recognition operations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\Rekognition\RekognitionClient;
use Aws\Exception\AwsException;

// Load environment variables from .env file
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception('.env file not found');
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Initialize AWS Rekognition Client
function getRekognitionClient()
{
    static $client = null;

    if ($client === null) {
        $client = new RekognitionClient([
            'version' => 'latest',
            'region' => getenv('AWS_REGION') ?: 'ap-south-1',
            'credentials' => [
                'key' => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY')
            ]
        ]);
    }

    return $client;
}

/**
 * Create AWS Rekognition Collection if it doesn't exist
 * @return array Response with success status and message
 */
function createFaceCollection()
{
    try {
        $client = getRekognitionClient();
        $collectionId = getenv('AWS_REKOGNITION_COLLECTION') ?: 'hrms-faces';

        // Check if collection exists
        try {
            $client->describeCollection([
                'CollectionId' => $collectionId
            ]);
            return ['success' => true, 'message' => 'Collection already exists'];
        } catch (AwsException $e) {
            // Collection doesn't exist, create it
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                $result = $client->createCollection([
                    'CollectionId' => $collectionId
                ]);
                return [
                    'success' => true,
                    'message' => 'Collection created successfully',
                    'data' => $result
                ];
            }
            throw $e;
        }
    } catch (AwsException $e) {
        return [
            'success' => false,
            'message' => 'AWS Error: ' . $e->getAwsErrorMessage(),
            'error_code' => $e->getAwsErrorCode()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Index (enroll) a face into AWS Rekognition collection
 * @param string $imageBlob Base64 encoded image data
 * @param int $employeeId Employee ID to use as external ID
 * @return array Response with face ID, image ID, and confidence
 */
function indexFace($imageBlob, $employeeId)
{
    try {
        $client = getRekognitionClient();
        $collectionId = getenv('AWS_REKOGNITION_COLLECTION') ?: 'hrms-faces';

        // Decode base64 image
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageBlob));

        $result = $client->indexFaces([
            'CollectionId' => $collectionId,
            'Image' => [
                'Bytes' => $imageData
            ],
            'ExternalImageId' => 'employee_' . $employeeId,
            'DetectionAttributes' => ['ALL'],
            'MaxFaces' => 1,
            'QualityFilter' => 'AUTO'
        ]);

        if (empty($result['FaceRecords'])) {
            return [
                'success' => false,
                'message' => 'No face detected in the image. Please ensure your face is clearly visible.'
            ];
        }

        $faceRecord = $result['FaceRecords'][0];
        $faceDetail = $faceRecord['FaceDetail'];

        return [
            'success' => true,
            'face_id' => $faceRecord['Face']['FaceId'],
            'image_id' => $faceRecord['Face']['ImageId'],
            'confidence' => round($faceRecord['Face']['Confidence'], 2),
            'quality' => [
                'brightness' => $faceDetail['Quality']['Brightness'],
                'sharpness' => $faceDetail['Quality']['Sharpness']
            ]
        ];

    } catch (AwsException $e) {
        return [
            'success' => false,
            'message' => 'AWS Error: ' . $e->getAwsErrorMessage(),
            'error_code' => $e->getAwsErrorCode()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Search for a face in the collection by image
 * @param string $imageBlob Base64 encoded image data
 * @param float $threshold Minimum confidence threshold (default 80%)
 * @return array Response with matched face ID, employee ID, and confidence
 */
function searchFaceByImage($imageBlob, $threshold = 80.0)
{
    try {
        $client = getRekognitionClient();
        $collectionId = getenv('AWS_REKOGNITION_COLLECTION') ?: 'hrms-faces';

        // Decode base64 image
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageBlob));

        $result = $client->searchFacesByImage([
            'CollectionId' => $collectionId,
            'Image' => [
                'Bytes' => $imageData
            ],
            'FaceMatchThreshold' => $threshold,
            'MaxFaces' => 1
        ]);

        if (empty($result['FaceMatches'])) {
            return [
                'success' => false,
                'message' => 'Face not recognized. Please try again or use manual punch.',
                'no_match' => true
            ];
        }

        $match = $result['FaceMatches'][0];
        $externalImageId = $match['Face']['ExternalImageId'];

        // Extract employee ID from external image ID (format: employee_123)
        $employeeId = (int) str_replace('employee_', '', $externalImageId);

        return [
            'success' => true,
            'face_id' => $match['Face']['FaceId'],
            'employee_id' => $employeeId,
            'confidence' => round($match['Similarity'], 2),
            'image_id' => $match['Face']['ImageId']
        ];

    } catch (AwsException $e) {
        $errorCode = $e->getAwsErrorCode();

        if ($errorCode === 'InvalidParameterException') {
            return [
                'success' => false,
                'message' => 'No face detected in the image. Please ensure your face is clearly visible and well-lit.'
            ];
        }

        return [
            'success' => false,
            'message' => 'AWS Error: ' . $e->getAwsErrorMessage(),
            'error_code' => $errorCode
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Delete a face from the collection
 * @param string $faceId AWS Face ID to delete
 * @return array Response with success status
 */
function deleteFace($faceId)
{
    try {
        $client = getRekognitionClient();
        $collectionId = getenv('AWS_REKOGNITION_COLLECTION') ?: 'hrms-faces';

        $result = $client->deleteFaces([
            'CollectionId' => $collectionId,
            'FaceIds' => [$faceId]
        ]);

        return [
            'success' => true,
            'message' => 'Face deleted successfully',
            'deleted_faces' => $result['DeletedFaces']
        ];

    } catch (AwsException $e) {
        return [
            'success' => false,
            'message' => 'AWS Error: ' . $e->getAwsErrorMessage(),
            'error_code' => $e->getAwsErrorCode()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * List all faces in the collection
 * @return array Response with list of faces
 */
function listFacesInCollection()
{
    try {
        $client = getRekognitionClient();
        $collectionId = getenv('AWS_REKOGNITION_COLLECTION') ?: 'hrms-faces';

        $result = $client->listFaces([
            'CollectionId' => $collectionId,
            'MaxResults' => 100
        ]);

        return [
            'success' => true,
            'faces' => $result['Faces'],
            'count' => count($result['Faces'])
        ];

    } catch (AwsException $e) {
        return [
            'success' => false,
            'message' => 'AWS Error: ' . $e->getAwsErrorMessage(),
            'error_code' => $e->getAwsErrorCode()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}
?>