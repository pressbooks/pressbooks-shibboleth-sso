<?php

namespace PressbooksSamlSso\Log;

interface StorageProvider {
	public function store( string $data );
}
