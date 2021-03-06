<?php

namespace PressbooksSamlSso\Log;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException as AwsException;
use Aws\S3\S3Client as S3Client;
use function Pressbooks\Utility\debug_error_log;

class S3StorageProvider implements StorageProvider {

	/**
	 * @var string
	 */
	private $bucket_name;

	/**
	 * @var string
	 */
	private $secret_key;

	/**
	 * @var string
	 */
	private $access_key_id;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $region;

	/**
	 * @var S3Client
	 */
	private $client;

	/**
	 * @var string
	 */
	private $file_path;

	const FILENAME_FORMAT = '-saml_logs.csv';

	const AWS_CONFIG_FILENAME = 'does_not_exist.ini';

	const AWS_MAIN_LOG_FOLDER = 'saml_logs';

	private function create() {
		if ( self::areEnvironmentVariablesPresent() ) {
			$this->region = env( 'AWS_S3_REGION' );
			$this->version = env( 'AWS_S3_VERSION' );
			$this->bucket_name = env( 'AWS_S3_OIDC_BUCKET' );
			$this->access_key_id = env( 'AWS_ACCESS_KEY_ID' );
			$this->secret_key = env( 'AWS_SECRET_ACCESS_KEY' );
			$environment = env( 'WP_ENV' ) ? env( 'WP_ENV' ) : 'production';
			$scheme = is_ssl() ? 'https' : 'http';
			if ( is_null( $this->file_path ) ) {
				$this->file_path = 's3://' . $this->bucket_name . '/' . self::AWS_MAIN_LOG_FOLDER . '/' . $environment .
					'/' . wp_hash( network_home_url( '', $scheme ) ) . '/' . current_time( 'Y-m' ) . self::FILENAME_FORMAT;
			}
			if ( is_null( $this->client ) ) {
				$this->client = new S3Client( [
					'region' => $this->region,
					'version' => $this->version,
					'credentials' => CredentialProvider::env(),
				] );
			}
			return true;
		} else {
			debug_error_log( 'Error initializing S3 Storage Provider: Some environment variables are not present.' );
		}
		return false;
	}

	/**
	 * NOTE: We will use the same environment variables for OIDC plugin temporary.
	 *
	 * @return bool
	 */
	public static function areEnvironmentVariablesPresent() {
		if (
			! is_null( env( 'AWS_S3_OIDC_BUCKET' ) ) &&
			! is_null( env( 'AWS_SECRET_ACCESS_KEY' ) ) &&
			! is_null( env( 'AWS_ACCESS_KEY_ID' ) ) &&
			! is_null( env( 'AWS_S3_VERSION' ) ) &&
			! is_null( env( 'AWS_S3_REGION' ) )
		) {
			if ( is_null( env( 'AWS_CONFIG_FILE' ) ) ) {
				// this is an env variable needed for AWS SDK
				putenv( 'AWS_CONFIG_FILE=' . __DIR__ . '/' . self::AWS_CONFIG_FILENAME );
			}
			return true;
		} else {
			debug_error_log( 'Error initializing S3 Storage Provider: Some environment variables are not present.' );
		}
		return false;
	}

	public function setS3Client( S3Client $s3_client ) {
		$this->client = $s3_client;
	}

	public function setFilePath( string $file_path ) {
		$this->file_path = $file_path;
	}

	public function store( string $data, string $file_header = null ) {
		if ( $this->create() ) {
			try {
				$this->client->registerStreamWrapper();
				$stream = fopen( $this->file_path, 'a' );
				if ( ! is_null( $file_header ) && ! file_exists( $this->file_path ) ) {
					$data = $file_header . $data;
				}
				fwrite( $stream, $data );
				fclose( $stream );
				return true;
			} catch ( AwsException $e ) {
				debug_error_log( 'Error saving SAML logs in S3: ' . $e->getMessage() );
			}
		}
		return false;
	}
}
