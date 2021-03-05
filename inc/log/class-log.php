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

	public function __construct( StorageProvider $store_provider ) {
		$this->store_provider = $store_provider;
		$this->data = [];
	}

	public function addRowToData( string $key, array $value ) {
		$this->data[$key] = $value;
	}

	public function store() {
		$data_string_format = '-------------------- START LINE BLOCK --------------------';
		$data_string_format .= $this->getDataFormatForStorage();
		$data_string_format .= '-------------------- END LINE BLOCK --------------------' . "\n \n";
		$this->store_provider->store( $data_string_format );
	}

	private function addDateToData() {
		if ( ! array_key_exists( 'Date', $this->data ) ) {
			$this->data['Date'] = date( 'Y-m-d H:i:s' );
		}
	}

	private function getDataFormatForStorage() {
		$this->addDateToData();
		return print_r( $this->data, true );
	}

}
