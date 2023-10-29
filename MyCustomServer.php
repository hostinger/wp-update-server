<?php

class MyCustomServer extends Wpup_UpdateServer {
	public function handleRequest($query = null, $headers = null) {
		$this->startTime = microtime(true);
		$request = $this->initRequest($query, $headers);

		$this->loadPackageFor($request);
		$this->validateRequest($request);
		$this->checkAuthorization($request);

		// Generate the ETag based on the package's content
		$etag = md5(file_get_contents($request->package->getFilename()));

		// Check if the client's ETag matches the one generated
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
			// If the ETags match, the resource hasn't changed, so respond with a 304 Not Modified status
			header('HTTP/1.1 304 Not Modified');
			exit();
		}

		// If the ETags don't match or the client didn't provide one, serve the resource and set the ETag in the response header
		header('ETag: "' . $etag . '"');

		$this->dispatch($request);
		exit;
	}

	protected function actionDownload(Wpup_Request $request) {
		$package = $request->package;
		// Set cache headers for 3 hours (3 hours * 3600 seconds)
		$cacheTime = 3 * 3600;
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $package->slug . '.zip"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $package->getFileSize());
		header('Cache-Control: public, max-age=' . $cacheTime);

		readfile($package->getFilename());
	}
}