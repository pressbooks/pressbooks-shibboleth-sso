<?php

use Aws\S3\S3Client as S3Client;
use PressbooksSamlSso\Log;

class LogTest extends \WP_UnitTestCase {

	use \phpmock\phpunit\PHPMock;

	/**
	 * @var S3Client
	 */
	private $s3_provider_mock;

	/**
	 * @var Log\Log
	 */
	private $log;

	const TEST_FILE_PATH = 'tests/data/log.csv';

	/**
	 * Test setup
	 */
	public function setUp() {
		$this->setEnvironmentVariables();

		$s3_client_mock = $this
			->getMockBuilder( S3Client::class )
			->disableOriginalConstructor()
			->setMethods([
				'registerStreamWrapper',
			])
			->getMock();
		$this->s3_provider_mock = new Log\S3StorageProvider();
		$this->s3_provider_mock->setS3Client( $s3_client_mock );
		$this->s3_provider_mock->setFilePath( self::TEST_FILE_PATH );
		$this->log = new Log\Log( $this->s3_provider_mock );
	}

	private function setEnvironmentVariables() {
		putenv( 'AWS_S3_OIDC_BUCKET=fakeBucket' );
		putenv( 'AWS_SECRET_ACCESS_KEY=fakeAccessKey' );
		putenv( 'AWS_ACCESS_KEY_ID=fakeKeyId' );
		putenv( 'AWS_S3_VERSION=fakeVersion' );
		putenv( 'AWS_S3_REGION=fakeRegion' );
	}

	/**
	 * @group log
	 */
	public function test_store() {
		$this->log->addRowToData( 'Test key 1', ['Test value'] );
		$this->log->addRowToData( 'Test key 2', [
			'Test a' => 'Test b',
			'Test c' => 'Test d',
		] );
		$this->assertTrue( $this->log->store() );
		$file_content = str_getcsv( file_get_contents( self::TEST_FILE_PATH ) );
		unlink( self::TEST_FILE_PATH );
		$this->assertEquals( 'Test key 1', $file_content[1] );
		$this->assertContains( 'Test value', $file_content[2] );
		$this->assertEquals( 'Test key 2', $file_content[3] );
		$this->assertContains( 'Test b', $file_content[4] );
		$this->assertContains( 'Test d', $file_content[4] );
		$this->assertContains( '[Test a] =>', $file_content[4] );
		$this->assertContains( '[Test c] =>', $file_content[4] );
	}
}
