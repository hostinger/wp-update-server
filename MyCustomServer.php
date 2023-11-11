<?php

class MyCustomServer extends Wpup_UpdateServer {
	public function handleRequest( $query = null, $headers = null ) {

		$this->startTime = microtime( true );
		$request         = $this->initRequest( $query, $headers );

		$this->loadPackageFor( $request );
		$this->validateRequest( $request );
		$this->checkAuthorization( $request );
		$this->dispatch( $request );
		exit;
	}

	protected function actionDownload( Wpup_Request $request ) {
		$package = $request->package;

		$directory = __DIR__ . '/packages/';
		$etag      = '"' . md5( filemtime( $directory ) ) . '"';

		// Check if the client's ETag matches the server's ETag, if so, send a 304 Not Modified response
		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $etag ) {
			header( 'HTTP/1.1 304 Not Modified' );
			exit;
		}

		// Set cache headers for 3 hours (3 hours * 3600 seconds)
		$cacheTime = 3 * 3600;
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $package->slug . '.zip"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . $package->getFileSize() );
		header( 'Cache-Control: public, max-age=' . $cacheTime );
		header( 'ETag: ' . $etag );

		readfile( $package->getFilename() );
	}

}
