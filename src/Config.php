<?php
/*
 * PoiXson Composer Local Dev - Links vendor data to a local workspace
 * @copyright 2019
 * @license GPL-3
 * @author lorenzo at poixson.com
 * @link http://poixson.com/
 */
namespace pxn\ComposerLocalDev;



class Config {

	protected $configFile;
	protected $paths;



	public function __construct($configFile) {
		$this->configFile = $configFile;
	}



	public function getPaths() {
		return $this->paths;
	}
	public function setPaths(array $paths) {
		$this->paths = $paths;
	}



	public function load() {
		$this->paths = [];
		$array = $this->readFromFile();
		if ($array === FALSE) return;
		if (isset($array['paths'])) {
			$this->paths = $array['paths'];
		}
	}
//	public function save() {
//		$array = $this->readFromFile();
//		if ($array === FALSE) return;
//		$array['paths'] = $this->paths;
//		$this->writeToFile($array);
//	}



	protected function readFromFile() {
		$data = \file_get_contents($this->configFile);
		if ($data === FALSE) return FALSE;
		return \json_decode($data, TRUE);
	}
//	protected function writeToFile(array $array) {
//		$data =
//			\json_encode(
//				$array,
//				\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
//			);
//		\file_put_contents($this->configFile, $data);
//	}



}
