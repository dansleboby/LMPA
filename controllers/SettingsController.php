<?php

namespace Controller;

use Lib\Config;

class SettingsController
{
	private \League\CLImate\CLImate $climate;
	private Config $config;

	public function __construct(\League\CLImate\CLImate $CLImate)
	{
		$this->climate = $CLImate;
		$this->config = new Config();
		$this->climate->clear();

		$this->climate->out("Settings");
		$this->climate->border();

		$action = menu([
			"Set PHP Extensions API token",
		]);

		if ($action == "0") {
			$this->setApiToken();
		}
	}

	private function setApiToken(): void
	{
		$current = $this->config->get('phpext_api_token');
		if (!empty($current)) {
			$this->climate->out("Current token: <green>" . substr($current, 0, 8) . "...</green>");
		} else {
			$this->climate->yellow("No token configured");
		}

		$this->climate->br();
		$this->climate->out("Get your token at: <yellow>https://phpext.phptools.online/account/api-token</yellow>");
		$this->climate->br();

		$token = $this->climate->lightGreen()->input("Paste your API token (or leave empty to cancel):")->prompt();

		if (!empty($token)) {
			$this->config->set('phpext_api_token', $token)->save();
			$this->climate->lightGreen("API token saved!");
		} else {
			$this->climate->yellow("No changes made");
		}
	}
}
