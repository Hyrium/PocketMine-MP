<?php


declare(strict_types=1);

namespace pocketmine\plugin;

use pocketmine\thread\ThreadSafeClassLoader;
use function file_exists;
use function is_dir;

class FolderPluginLoader implements PluginLoader {

	public function __construct(
		private readonly ThreadSafeClassLoader $loader
	){}

	public function canLoadPlugin(string $path): bool {
		return is_dir($path) and file_exists($path . "/plugin.yml") and file_exists($path . "/src/");
	}

	public function loadPlugin(string $file): void {
		$description = $this->getPluginDescription($file);
		if($description !== null) {
			$this->loader->addPath($description->getSrcNamespacePrefix(), "$file/src");
		}
	}

	public function getPluginDescription(string $file): ?PluginDescription {
		if(is_dir($file) and file_exists($file . "/plugin.yml")) {
			$yaml = @file_get_contents($file . "/plugin.yml");
			if($yaml != "") {
				return new PluginDescription($yaml);
			}
		}

		return null;
	}

	public function getAccessProtocol(): string {
		return "";
	}

}