<?php
/*
 * PoiXson ComposerLocalDev - Symlink vendor data to your local workspace
 * @copyright 2019
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\ComposerLocalDev;


class Config {

	protected $configFile;
	protected $paths = [];
	protected $depth = 0;



	public function __construct($configFile, ?int $depth=0) {
		$this->configFile = $configFile;
		$this->depth = $depth;
	}



	public function getConfigPath(): string {
		return $this->configFile;
	}



	public function getPaths() {
		return $this->paths;
	}
	public function setPaths(array $paths) {
		$this->paths = $paths;
	}



	public function getDepth(): int {
		return $this->depth;
	}



	public function isDev() {
		return (\count($this->paths) > 0);
	}



	public function load() {
		$this->paths = [];
		if ( ! \is_file($this->configFile) )
			return;
		$data = \file_get_contents($this->configFile);
		if ($data === FALSE)
			return;
		$array = \json_decode($data, TRUE);
		if ($array === FALSE)
			return;
		if (isset($array['paths'])) {
			foreach ($array['paths'] as $namespace => $devPath) {
				$this->paths[$namespace] = \str_repeat('../', $this->depth).$devPath;
			}
		}
	}



//	public function save() {
//		$array = $this->readFromFile();
//		if ($array === FALSE) return;
//		$array['paths'] = $this->paths;
//		$this->writeToFile($array);
//	}
//	protected function writeToFile(array $array) {
//		$data =
//			\json_encode(
//				$array,
//				\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
//			);
//		\file_put_contents($this->configFile, $data);
//	}



}
