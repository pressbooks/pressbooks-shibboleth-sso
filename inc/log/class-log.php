<?php

namespace PressbooksSamlSso\Log;

class Log {

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var StorageProvider
	 */
	private $store_provider;

	/**
	 * @var string
	 */
	private $file_header;

	const CSV_COLUMNS = [ 'Date', 'Key', 'Value' ];

	public function __construct( StorageProvider $store_provider ) {
		$this->store_provider = $store_provider;
		$this->data = [];
		$this->file_header = implode( ',', self::CSV_COLUMNS ) . "\n";
	}

	public function addRowToData( string $key, array $value ) {
		$this->data[ $key ] = $value;
	}

	public function store() {
		$data_string_format = $this->getDataFormatForStorage();
		return $this->store_provider->store( $data_string_format, $this->file_header );
	}

	private function getDataFormatForStorage() {
		$data_csv_format = '';
		$current_date = current_time( 'mysql' );
		foreach ( $this->data as $key => $value ) {
			$data_csv_format .= $current_date . ',' . $key . ',"' . print_r( $value, true ) . '"' . "\n"; // @codingStandardsIgnoreLine
		}
		return $data_csv_format;
	}

}
